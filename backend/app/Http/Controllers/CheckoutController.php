<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Variant;
use App\Models\Event;
use App\Models\Registration;
use App\Services\Payments\AsaasService;
use App\Services\Payments\AsaasInstallmentService;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function createOrder(Request $request, AsaasService $asaas)
    {
        $data = $request->validate([
            'buyer.name'            => 'required|string|max:255',
            'buyer.cpf'             => 'required|string|max:14',
            'buyer.email'           => 'nullable|email',
            'buyer.phone'           => 'nullable|string|max:20',
            'buyer.postalCode'      => 'required|string|max:9',
            'buyer.addressNumber'   => 'required|string|max:10',
            'buyer.address'         => 'required|string|max:255',
            'buyer.province'        => 'required|string|max:100',
            'buyer.city'            => 'required|string|max:100',
            'buyer.state'           => 'required|string|size:2',
            'buyer.addressComplement' => 'nullable|string|max:100',

            'sector_id'             => 'required|exists:sectors,id',
            'items'                 => 'required|array|min:1',
            'items.*.variant_id'    => 'required|exists:variants,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'payment.method'        => 'required|in:BOLETO,PIX,CREDIT_CARD',
            'payment.card'          => 'array',
            'payment.tax'           => 'array',
            'payment.installments'  => 'nullable|integer|min:1|max:21',
        ]);

        return DB::transaction(function () use ($data, $asaas) {
            $totalCents = 0;
            $itemsToInsert = [];

            foreach ($data['items'] as $it) {
                $variant = Variant::with('product')->find($it['variant_id']);
                $unitCents = (int) ($variant->price_override ?? $variant->product->base_price);
                $subCents  = $unitCents * (int) $it['quantity'];
                $totalCents += $subCents;

                $itemsToInsert[] = [
                    'variant'   => $variant,
                    'quantity'  => (int) $it['quantity'],
                    'unit'      => $unitCents,
                    'subtotal'  => $subCents,
                ];
            }

            // Calcular taxas ANTES de criar o Order
            // PIX não tem taxa - sempre usar valor sem taxa para PIX
            $fixCents  = 0;
            $percent   = 0.0;
            
            // Se não for PIX, aplicar taxas do payload
            if ($data['payment']['method'] !== 'PIX') {
                $fixCents  = (int) ($data['payment']['tax']['fix'] ?? 0);
                $percent   = (float) ($data['payment']['tax']['percent'] ?? 0);
            }
            
            $percentCents = (int) round($totalCents * ($percent / 100));
            $valueWithTaxCents = $totalCents + $fixCents + $percentCents;

            $customer = $asaas->ensureCustomer([
                'name'  => $data['buyer']['name'],
                'cpf'   => $data['buyer']['cpf'],
                'email' => $data['buyer']['email'] ?? null,
                'phone' => $data['buyer']['phone'] ?? null,
            ]);

            $order = Order::create([
                'uuid'             => (string) Str::uuid(),
                'order_number'     => strtoupper(Str::random(8)),
                'buyer_name'       => $data['buyer']['name'],
                'buyer_cpf'        => $data['buyer']['cpf'],
                'buyer_email'      => $data['buyer']['email'] ?? null,
                'buyer_phone'      => $data['buyer']['phone'] ?? null,
                'buyer_postal_code'=> $data['buyer']['postalCode'],
                'buyer_address'    => $data['buyer']['address'],
                'buyer_address_number' => $data['buyer']['addressNumber'],
                'buyer_address_complement' => $data['buyer']['addressComplement'] ?? null,
                'buyer_province'   => $data['buyer']['province'],
                'buyer_city'       => $data['buyer']['city'],
                'buyer_state'      => $data['buyer']['state'],
                'sector_id'        => $data['sector_id'],
                'total_amount'     => $valueWithTaxCents, // Salvar valor COM taxas em centavos
                'status'           => 'pending',
                'payment_method'   => $data['payment']['method'],
                'asaas_customer_id'=> $customer['id'] ?? null,
            ]);

            foreach ($itemsToInsert as $li) {
                $product = $li['variant']->product;

                OrderItem::create([
                    'order_id'           => $order->id,
                    'product_id'         => $product->id,
                    'variant_id'         => $li['variant']->id,
                    'quantity'           => $li['quantity'],
                    'unit_price'         => $li['unit'] / 100,
                    'subtotal'           => $li['subtotal'] / 100,
                    'product_name'       => $product->name,
                    'product_description'=> $product->description,
                    'product_data'       => json_encode([
                        'base_price'  => $product->base_price,
                        'old_price'   => $product->old_price,
                        'active'      => $product->active,
                        'infinite_stock' => $product->infinite_stock,
                    ]),
                    'variant_color'        => $variant->color,
                    'variant_fit'          => $variant->fit,
                    'variant_size'         => $variant->size,
                    'variant_data'         => json_encode([
                        'price_override' => $variant->price_override,
                        'active'         => $variant->active,
                        'featured'       => $variant->featured,
                    ]),
                ]);
            }

            $payload = [
                'customer'    => $order->asaas_customer_id,
                'billingType' => $data['payment']['method'],
                'description' => 'Pedido ' . $order->order_number,
                'dueDate' => now()->format('Y-m-d'),
            ];

            $installments = (int) ($data['payment']['installments'] ?? 1);

            if ($data['payment']['method'] === 'CREDIT_CARD') {
                if ($installments > 1) {
                    $installmentService = app(AsaasInstallmentService::class);
                    $charge = $installmentService->create([
                        'customer'         => $order->asaas_customer_id,
                        'description'      => 'Pedido ' . $order->order_number,
                        'dueDate'          => now()->addDays(3)->toDateString(),
                        'installmentCount' => $installments,
                        'totalValue'       => number_format($valueWithTaxCents / 100, 2, '.', ''),
                        'creditCard'       => $data['payment']['card'],
                        'creditCardHolderInfo' => [
                            'name'           => $data['buyer']['name'],
                            'email'          => $data['buyer']['email'] ?? null,
                            'cpfCnpj'        => $data['buyer']['cpf'],
                            'phone'          => $data['buyer']['phone'] ?? null,
                            'postalCode'     => $data['buyer']['postalCode'],
                            'addressNumber'  => $data['buyer']['addressNumber'],
                            'addressComplement' => $data['buyer']['addressComplement'] ?? null,
                            'address'        => $data['buyer']['address'],
                            'province'       => $data['buyer']['province'],
                            'city'           => $data['buyer']['city'],
                            'state'          => $data['buyer']['state'],
                        ],
                        'remoteIp' => request()->ip(),
                    ]);

                } else {
                    $cardToken = $asaas->tokenizeCard(
                        $order->asaas_customer_id,
                        $data['payment']['card']
                    );
                    $payload['creditCardToken'] = $cardToken['creditCardToken'] ?? null;
                    $payload['remoteIp']        = request()->ip();
                    $payload['value'] = number_format($valueWithTaxCents / 100, 2, '.', '');
                    $charge = $asaas->createCharge($payload);
                }
            } else {
                $payload['value'] = number_format($valueWithTaxCents / 100, 2, '.', '');
                $charge = $asaas->createCharge($payload);
            }

            if ($data['payment']['method'] === 'PIX' && isset($charge['id'])) {
                $pixQrCode = $asaas->getPixQrCode($charge['id']);
                $charge['pixQrCodeImage'] = $pixQrCode['encodedImage'] ?? null;
                $charge['pixQrCode']      = $pixQrCode['payload'] ?? null;
            }

            if ($data['payment']['method'] === 'BOLETO' && isset($charge['id'])) {
                $boletoData = $asaas->getBoletoDigitableLine($charge['id']);
                $charge['identificationField'] = $boletoData['identificationField'] ?? null;
            }

            $gatewayPayload = array_merge($charge, [
                'appliedFixTax'      => $fixCents / 100,
                'appliedPercentTax'  => $percent,
                'installmentCount'   => $charge['installmentCount'] ?? null,
                'installmentValue'   => $charge['installmentValue'] ?? null,
                'totalValue'         => $charge['totalValue'] ?? null,
            ]);

            // Atualizar o total_amount com o valor final retornado pelo Asaas (se disponível)
            // Isso garante que temos o valor exato que foi cobrado, incluindo taxas aplicadas pelo gateway
            $finalAmountCents = $valueWithTaxCents; // Valor calculado com taxas
            if (isset($charge['totalValue'])) {
                // Se o Asaas retornou um totalValue, usar esse (pode ter taxas adicionais do gateway)
                $finalAmountCents = (int) round((float) $charge['totalValue'] * 100);
            } elseif (isset($charge['value'])) {
                // Se não tem totalValue mas tem value, usar esse
                $finalAmountCents = (int) round((float) $charge['value'] * 100);
            }

            $order->update([
                'asaas_payment_id' => $charge['id'] ?? null,
                'gateway_payload'  => $gatewayPayload,
                'total_amount'     => $finalAmountCents, // Atualizar com valor final incluindo taxas
            ]);

            // Recarregar o order para ter os dados atualizados
            $order->refresh();

            try {
                $firebase = app(FirebaseService::class);
                $firebase->update("webhooks/orders_{$order->id}", [
                    'id'            => $order->id,
                    'order_number'  => $order->order_number,
                    'status'        => $order->status,
                    'total_amount'  => $order->total_amount,
                    'payment_method'=> $order->payment_method,
                    'buyer_cpf'     => $order->buyer_cpf,
                    'updated_at'    => now()->toISOString(),
                ]);
                $firebase->push("webhooks/orders_{$order->id}/events", [
                    'status'     => $order->status,
                    'payload'    => $charge,
                    'created_at' => now()->toISOString(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Erro ao atualizar Firebase no webhook', [
                    'order_id' => $order->id,
                    'message'  => $e->getMessage(),
                ]);
            }

            $response = [
                'orderNumber' => $order->order_number,
                'status'      => $order->status,
                'amount'      => $order->total_amount / 100, // Converter centavos para reais na resposta
                'payment'     => [
                    'method' => $data['payment']['method'],
                    'boletoUrl' => $charge['bankSlipUrl'] ?? null,
                    'digitableLine' => $charge['identificationField'] ?? null,
                    'pix' => [
                        'qrCodeImage' => $charge['pixQrCodeImage'] ?? null,
                        'payload'     => $charge['pixQrCode'] ?? null,
                    ],
                    'creditCard' => [
                        'status' => $charge['status'] ?? null,
                    ],
                    'tax' => [
                        'fix' => $fixCents / 100,
                        'percent' => $percent,
                    ],
                ],
            ];

            return response()->json($response, 201);
        });
    }

    public function createEventRegistrations(Request $request, AsaasService $asaas)
    {
        // Primeiro validar dados básicos
        $baseRules = [
            'buyer.name'            => 'required|string|max:255',
            'buyer.cpf'             => 'required|string|max:14',
            'buyer.email'           => 'nullable|email',
            'buyer.phone'           => 'nullable|string|max:20',
            'events'                => 'required|array|min:1',
            'events.*.event_id'     => 'required|exists:events,id',
            'events.*.quantity'      => 'required|integer|min:1|max:10',
            'events.*.registrations' => 'required|array|min:1',
            'events.*.registrations.*.name' => 'required|string|max:255',
            'events.*.registrations.*.phone' => 'required|string|max:20',
            'events.*.registrations.*.cpf' => 'nullable|string|max:14',
            'events.*.registrations.*.birth_date' => 'required|date',
            'events.*.registrations.*.sector' => 'nullable|string|max:10',
            'events.*.registrations.*.congregation' => 'nullable|string|max:255',
            'events.*.registrations.*.church_type' => 'nullable|string|max:255',
            'events.*.registrations.*.gender' => 'required|in:MASCULINO,FEMININO',
            'events.*.registrations.*.whatsapp_authorization' => 'nullable|boolean',
            'payment.method'        => 'required|in:BOLETO,PIX,CREDIT_CARD,FREE',
            'payment.card'          => 'nullable|array',
            'payment.installments'  => 'nullable|integer|min:1|max:21',
            'payment.tax'           => 'nullable|array',
        ];

        // Verificar se há eventos pagos para exigir endereço
        $hasPaidEvents = false;
        if ($request->has('events')) {
            foreach ($request->input('events') as $eventData) {
                $event = Event::find($eventData['event_id'] ?? null);
                if ($event && $event->price > 0) {
                    $hasPaidEvents = true;
                    break;
                }
            }
        }

        // Se houver eventos pagos, exigir endereço
        if ($hasPaidEvents) {
            $baseRules['buyer.postalCode'] = 'required|string|max:9';
            $baseRules['buyer.addressNumber'] = 'required|string|max:10';
            $baseRules['buyer.address'] = 'required|string|max:255';
            $baseRules['buyer.province'] = 'required|string|max:100';
            $baseRules['buyer.city'] = 'required|string|max:100';
            $baseRules['buyer.state'] = 'required|string|size:2';
            $baseRules['buyer.addressComplement'] = 'nullable|string|max:100';
        }

        $data = $request->validate($baseRules);

        return DB::transaction(function () use ($data, $asaas) {
            $allRegistrations = [];
            $totalAmount = 0;

            // Processar cada evento
            foreach ($data['events'] as $eventData) {
                $event = Event::with('paymentMethods')->findOrFail($eventData['event_id']);

                // Validar se o evento ainda aceita inscrições
                $today = now()->format('Y-m-d');
                if ($event->end_date < $today) {
                    continue; // Pular eventos encerrados
                }

                if (!$event->active) {
                    continue; // Pular eventos inativos
                }

                $eventTotal = $event->price * count($eventData['registrations']);
                $totalAmount += $eventTotal;

                // Criar registrations para este evento
                foreach ($eventData['registrations'] as $regData) {
                    $cpf = !empty($regData['cpf']) ? preg_replace('/\D/', '', $regData['cpf']) : null;
                    
                    $registration = Registration::create([
                        'event_id' => $event->id,
                        'name' => $regData['name'],
                        'phone' => $regData['phone'],
                        'cpf' => $cpf,
                        'birth_date' => $regData['birth_date'],
                        'sector' => $regData['sector'] ?? null,
                        'congregation' => $regData['congregation'] ?? null,
                        'church_type' => $regData['church_type'] ?? null,
                        'gender' => $regData['gender'],
                        'whatsapp_authorization' => $regData['whatsapp_authorization'] ?? false,
                        'price_paid' => $event->price,
                        'payment_method' => $data['payment']['method'],
                        'payment_status' => 'pending',
                    ]);

                    $allRegistrations[] = $registration;
                }
            }

            // Se for gratuito, retornar direto
            if ($data['payment']['method'] === 'FREE' || $totalAmount === 0) {
                foreach ($allRegistrations as $registration) {
                    $registration->update([
                        'payment_status' => 'paid',
                        'price_paid' => 0,
                    ]);
                }

                return response()->json([
                    'message' => 'Inscrições criadas com sucesso.',
                    'registrations' => $allRegistrations,
                    'payment' => [
                        'method' => 'FREE',
                        'amount' => 0,
                    ],
                ], 201);
            }

            // Calcular taxas ANTES de processar pagamento
            $fixCents = 0;
            $percent = 0.0;
            $installments = (int) ($data['payment']['installments'] ?? 1);

            // Se as taxas vieram no payload, usar elas
            if (isset($data['payment']['tax'])) {
                $fixCents = (int) ($data['payment']['tax']['fix'] ?? 0);
                $percent = (float) ($data['payment']['tax']['percent'] ?? 0);
            } else {
                // Calcular taxas baseado no método de pagamento (mesma lógica do frontend)
                // PIX não tem taxa - sempre usar valor sem taxa
                if ($data['payment']['method'] === 'PIX') {
                    $fixCents = 0; // PIX não tem taxa
                    $percent = 0;
                } elseif ($data['payment']['method'] === 'BOLETO') {
                    $fixCents = 199; // R$ 1,99
                    $percent = 0;
                } elseif ($data['payment']['method'] === 'CREDIT_CARD') {
                    $fixCents = 49; // R$ 0,49
                    // Calcular percentual baseado no número de parcelas
                    if ($installments === 1) {
                        $percent = 2.99;
                    } elseif ($installments >= 2 && $installments <= 6) {
                        $percent = 3.49 + (1.70 * $installments);
                    } elseif ($installments >= 7 && $installments <= 12) {
                        $percent = 3.99 + (1.70 * $installments);
                    } elseif ($installments >= 13 && $installments <= 21) {
                        $percent = 4.29 + (1.70 * $installments);
                    }
                }
            }

            $percentCents = (int) round($totalAmount * ($percent / 100));
            $valueWithTaxCents = $totalAmount + $fixCents + $percentCents;

            // Processar pagamento
            $customerData = [
                'name'  => $data['buyer']['name'],
                'cpf'   => $data['buyer']['cpf'],
                'email' => $data['buyer']['email'] ?? null,
                'phone' => $data['buyer']['phone'] ?? null,
            ];

            // Adicionar endereço se disponível
            if (!empty($data['buyer']['postalCode'])) {
                $customerData['postalCode'] = preg_replace('/\D/', '', $data['buyer']['postalCode']);
                $customerData['address'] = $data['buyer']['address'] ?? null;
                $customerData['addressNumber'] = $data['buyer']['addressNumber'] ?? null;
                $customerData['addressComplement'] = $data['buyer']['addressComplement'] ?? null;
                $customerData['province'] = $data['buyer']['province'] ?? null;
                $customerData['city'] = $data['buyer']['city'] ?? null;
                $customerData['state'] = strtoupper($data['buyer']['state'] ?? '');
            }

            $customer = $asaas->ensureCustomer($customerData);

            $payload = [
                'customer' => $customer['id'] ?? null,
                'billingType' => $data['payment']['method'],
                'description' => 'Inscrições em Eventos',
                'dueDate' => now()->addDays(1)->format('Y-m-d'), // Adicionar 1 dia para dar tempo de pagamento
            ];

            if ($data['payment']['method'] === 'CREDIT_CARD') {
                if (!isset($data['payment']['card'])) {
                    return response()->json([
                        'message' => 'Dados do cartão são obrigatórios para pagamento com cartão de crédito.'
                    ], 422);
                }

                if ($installments > 1) {
                    $installmentService = app(AsaasInstallmentService::class);
                    $charge = $installmentService->create([
                        'customer' => $customer['id'] ?? null,
                        'description' => 'Inscrições em Eventos',
                        'dueDate' => now()->addDays(3)->toDateString(),
                        'installmentCount' => $installments,
                        'totalValue' => number_format($valueWithTaxCents / 100, 2, '.', ''), // Enviar valor COM taxas
                        'creditCard' => $data['payment']['card'],
                        'creditCardHolderInfo' => [
                            'name' => $data['buyer']['name'],
                            'email' => $data['buyer']['email'] ?? null,
                            'cpfCnpj' => $data['buyer']['cpf'],
                            'phone' => $data['buyer']['phone'] ?? null,
                            'postalCode' => preg_replace('/\D/', '', $data['buyer']['postalCode']),
                            'addressNumber' => $data['buyer']['addressNumber'],
                            'addressComplement' => $data['buyer']['addressComplement'] ?? null,
                            'address' => $data['buyer']['address'],
                            'province' => $data['buyer']['province'],
                            'city' => $data['buyer']['city'],
                            'state' => strtoupper($data['buyer']['state']),
                        ],
                        'remoteIp' => request()->ip(),
                    ]);
                } else {
                    $cardToken = $asaas->tokenizeCard(
                        $customer['id'] ?? null,
                        $data['payment']['card']
                    );
                    $payload['creditCardToken'] = $cardToken['creditCardToken'] ?? null;
                    $payload['remoteIp'] = request()->ip();
                    $payload['value'] = number_format($valueWithTaxCents / 100, 2, '.', ''); // Enviar valor COM taxas
                    $charge = $asaas->createCharge($payload);
                }
            } else {
                $payload['value'] = number_format($valueWithTaxCents / 100, 2, '.', ''); // Enviar valor COM taxas
                $charge = $asaas->createCharge($payload);
            }

            // Atualizar o valor final com o retornado pelo Asaas (se disponível)
            $finalAmountCents = $valueWithTaxCents;
            if (isset($charge['totalValue'])) {
                $finalAmountCents = (int) round((float) $charge['totalValue'] * 100);
            } elseif (isset($charge['value'])) {
                $finalAmountCents = (int) round((float) $charge['value'] * 100);
            }

            // Calcular valor proporcional por registration (total com taxas / número de registrations)
            $registrationsCount = count($allRegistrations);
            $pricePerRegistrationCents = $registrationsCount > 0 
                ? (int) round($finalAmountCents / $registrationsCount) 
                : 0;

            // Atualizar registrations com payment_id e price_paid proporcional
            if (isset($charge['id'])) {
                foreach ($allRegistrations as $registration) {
                    $registration->update([
                        'asaas_customer_id' => $customer['id'] ?? null,
                        'asaas_payment_id' => $charge['id'],
                        'gateway_payload' => array_merge($charge, [
                            'appliedFixTax' => $fixCents / 100,
                            'appliedPercentTax' => $percent,
                        ]),
                        'price_paid' => $pricePerRegistrationCents, // Valor proporcional com taxas
                    ]);
                }
            }

            // Adicionar QR Code PIX se for PIX
            if ($data['payment']['method'] === 'PIX' && isset($charge['id'])) {
                $pixQrCode = $asaas->getPixQrCode($charge['id']);
                $charge['pixQrCodeImage'] = $pixQrCode['encodedImage'] ?? null;
                $charge['pixQrCode'] = $pixQrCode['payload'] ?? null;
            }

            // Adicionar linha digitável do boleto se for BOLETO
            if ($data['payment']['method'] === 'BOLETO' && isset($charge['id'])) {
                $boletoData = $asaas->getBoletoDigitableLine($charge['id']);
                $charge['identificationField'] = $boletoData['identificationField'] ?? null;
            }

            return response()->json([
                'message' => 'Inscrições criadas com sucesso.',
                'registrations' => $allRegistrations,
                'payment' => [
                    'method' => $data['payment']['method'],
                    'amount' => $finalAmountCents / 100, // Retornar valor final COM taxas em reais
                    'pix' => [
                        'qrCodeImage' => $charge['pixQrCodeImage'] ?? null,
                        'payload' => $charge['pixQrCode'] ?? null,
                    ],
                    'boletoUrl' => $charge['bankSlipUrl'] ?? null,
                    'digitableLine' => $charge['identificationField'] ?? null,
                    'creditCard' => [
                        'status' => $charge['status'] ?? null,
                    ],
                    'tax' => [
                        'fix' => $fixCents / 100,
                        'percent' => $percent,
                    ],
                ],
            ], 201);
        });
    }

    public function simulateInstallments(Request $request, AsaasService $asaas)
    {
        $value = (float) $request->input('value');
        $max = (int) ($request->input('max', 10));

        try {
            $result = $asaas->simulateInstallments($value, $max);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao simular parcelamento: ' . $e->getMessage()
            ], 500);
        }
    }
}

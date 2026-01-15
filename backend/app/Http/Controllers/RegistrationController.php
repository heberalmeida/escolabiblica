<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Services\Payments\AsaasService;
use App\Services\Payments\AsaasInstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $eventId = $request->input('event_id');

        $query = Registration::with('event');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $registrations = $query->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($registrations, 200);
    }

    public function store(Request $request, AsaasService $asaas)
    {
        $data = $request->validate([
            'event_id' => 'required|exists:events,id',
            'quantity' => 'required|integer|min:1|max:10',
            'registrations' => 'required|array|min:1',
            'registrations.*.name' => 'required|string|max:255',
            'registrations.*.phone' => 'required|string|max:20',
            'registrations.*.cpf' => 'nullable|string|max:14',
            'registrations.*.birth_date' => 'required|date',
            'registrations.*.sector' => 'nullable|string|max:10',
            'registrations.*.congregation' => 'nullable|string|max:255',
            'registrations.*.church_type' => 'nullable|string|max:255',
            'registrations.*.gender' => 'required|in:MASCULINO,FEMININO',
            'registrations.*.whatsapp_authorization' => 'nullable|boolean',
            'payment_method' => 'required|in:PIX,BOLETO,CREDIT_CARD,FREE',
            'buyer' => 'nullable|array',
            'buyer.name' => 'nullable|string|max:255',
            'buyer.cpf' => 'nullable|string|max:14',
            'buyer.email' => 'nullable|email|max:255',
            'buyer.phone' => 'nullable|string|max:20',
            'buyer.postalCode' => 'nullable|string|max:10',
            'buyer.addressNumber' => 'nullable|string|max:20',
            'buyer.addressComplement' => 'nullable|string|max:255',
            'buyer.address' => 'nullable|string|max:255',
            'buyer.province' => 'nullable|string|max:255',
            'buyer.city' => 'nullable|string|max:255',
            'buyer.state' => 'nullable|string|max:2',
            'card' => 'nullable|array',
            'card.holderName' => 'nullable|string|max:255',
            'card.number' => 'nullable|string|max:20',
            'card.expiryMonth' => 'nullable|string|max:2',
            'card.expiryYear' => 'nullable|string|max:4',
            'card.ccv' => 'nullable|string|max:4',
            'installments' => 'nullable|integer|min:1|max:21',
        ]);

        $event = Event::with('paymentMethods')->findOrFail($data['event_id']);

        // Validar se o evento ainda aceita inscrições (até o último dia)
        $today = now()->format('Y-m-d');
        if ($event->end_date < $today) {
            return response()->json([
                'message' => 'As inscrições para este evento foram encerradas. O evento terminou em ' . \Carbon\Carbon::parse($event->end_date)->format('d/m/Y') . '.'
            ], 422);
        }

        // Validar se o evento está ativo
        if (!$event->active) {
            return response()->json([
                'message' => 'Este evento não está mais disponível para inscrições.'
            ], 422);
        }

        // Validar método de pagamento
        $allowedMethods = $event->paymentMethods()->where('active', true)->pluck('method')->toArray();
        if (!in_array($data['payment_method'], $allowedMethods)) {
            return response()->json([
                'message' => 'Método de pagamento não disponível para este evento.'
            ], 422);
        }

        // Se é gratuito, não precisa processar pagamento
        if ($data['payment_method'] === 'FREE' || $event->isFree()) {
            return $this->createFreeRegistrations($event, $data);
        }

        // Processar pagamento
        return $this->createPaidRegistrations($event, $data, $asaas);
    }

    private function createFreeRegistrations(Event $event, array $data)
    {
        return DB::transaction(function () use ($event, $data) {
            $registrations = [];

            foreach ($data['registrations'] as $regData) {
                // Limpar CPF de caracteres não numéricos
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
                    'price_paid' => 0,
                    'payment_method' => 'FREE',
                    'payment_status' => 'paid',
                ]);

                $registrations[] = $registration;
            }

            return response()->json([
                'message' => 'Inscrições criadas com sucesso.',
                'registrations' => $registrations,
            ], 201);
        });
    }

    private function createPaidRegistrations(Event $event, array $data, AsaasService $asaas)
    {
        $totalAmount = $event->price * count($data['registrations']);

        return DB::transaction(function () use ($event, $data, $asaas, $totalAmount) {
            // Criar customer no Asaas (usar buyer se disponível, senão primeiro registro)
            $buyer = $data['buyer'] ?? [];
            $firstReg = $data['registrations'][0];
            
            $customer = [];
            try {
                // Usar dados do buyer se disponível, senão usar primeiro registro
                $name = $buyer['name'] ?? $firstReg['name'];
                $phone = preg_replace('/\D/', '', $buyer['phone'] ?? $firstReg['phone']);
                $cpf = !empty($buyer['cpf']) ? preg_replace('/\D/', '', $buyer['cpf']) : 
                       (!empty($firstReg['cpf']) ? preg_replace('/\D/', '', $firstReg['cpf']) : null);
                $email = $buyer['email'] ?? null;
                
                $customerData = [
                    'name' => $name,
                    'cpf' => $cpf,
                    'email' => $email,
                    'phone' => $phone,
                ];
                
                // Adicionar endereço se disponível
                if (!empty($buyer['postalCode'])) {
                    $customerData['postalCode'] = preg_replace('/\D/', '', $buyer['postalCode']);
                    $customerData['address'] = $buyer['address'] ?? null;
                    $customerData['addressNumber'] = $buyer['addressNumber'] ?? null;
                    $customerData['addressComplement'] = $buyer['addressComplement'] ?? null;
                    $customerData['province'] = $buyer['province'] ?? null;
                    $customerData['city'] = $buyer['city'] ?? null;
                    $customerData['state'] = strtoupper($buyer['state'] ?? '');
                }
                
                $customer = $asaas->ensureCustomer($customerData);
            } catch (\Exception $e) {
                // Se falhar, continuar sem customer_id
                \Log::warning('Erro ao criar customer no Asaas: ' . $e->getMessage());
            }

            // Criar registrations
            $registrations = [];
            foreach ($data['registrations'] as $regData) {
                // Limpar CPF de caracteres não numéricos
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
                    'payment_method' => $data['payment_method'],
                    'payment_status' => 'pending',
                    'asaas_customer_id' => $customer['id'] ?? null,
                ]);

                $registrations[] = $registration;
            }

            // Criar charge no Asaas
            $payload = [
                'customer' => $customer['id'] ?? null,
                'billingType' => $data['payment_method'],
                'description' => "Inscrição(s) - {$event->name}",
                'dueDate' => now()->format('Y-m-d'),
            ];

            $installments = (int) ($data['installments'] ?? 1);

            if ($data['payment_method'] === 'CREDIT_CARD') {
                if (!isset($data['card'])) {
                    return response()->json([
                        'message' => 'Dados do cartão são obrigatórios para pagamento com cartão de crédito.'
                    ], 422);
                }

                if ($installments > 1) {
                    $installmentService = app(AsaasInstallmentService::class);
                    $charge = $installmentService->create([
                        'customer' => $customer['id'] ?? null,
                        'description' => "Inscrição(s) - {$event->name}",
                        'dueDate' => now()->addDays(3)->toDateString(),
                        'installmentCount' => $installments,
                        'totalValue' => number_format($totalAmount / 100, 2, '.', ''),
                        'creditCard' => $data['card'],
                        'creditCardHolderInfo' => [
                            'name' => $buyer['name'] ?? $firstReg['name'],
                            'email' => $buyer['email'] ?? null,
                            'cpfCnpj' => $cpf,
                            'phone' => $phone,
                            'postalCode' => preg_replace('/\D/', '', $buyer['postalCode'] ?? ''),
                            'addressNumber' => $buyer['addressNumber'] ?? null,
                            'addressComplement' => $buyer['addressComplement'] ?? null,
                            'address' => $buyer['address'] ?? null,
                            'province' => $buyer['province'] ?? null,
                            'city' => $buyer['city'] ?? null,
                            'state' => strtoupper($buyer['state'] ?? ''),
                        ],
                        'remoteIp' => request()->ip(),
                    ]);
                } else {
                    $cardToken = $asaas->tokenizeCard(
                        $customer['id'] ?? null,
                        $data['card']
                    );
                    $payload['creditCardToken'] = $cardToken['creditCardToken'] ?? null;
                    $payload['remoteIp'] = request()->ip();
                    $payload['value'] = number_format($totalAmount / 100, 2, '.', '');
                    $charge = $asaas->createCharge($payload);
                }
            } else {
                $payload['value'] = number_format($totalAmount / 100, 2, '.', '');
                $charge = $asaas->createCharge($payload);
            }

            // Atualizar registrations com payment_id
            if (isset($charge['id'])) {
                foreach ($registrations as $registration) {
                    $registration->update([
                        'asaas_payment_id' => $charge['id'],
                        'gateway_payload' => $charge,
                    ]);
                }
            }

            // Adicionar QR Code PIX se for PIX
            if ($data['payment_method'] === 'PIX' && isset($charge['id'])) {
                $pixQrCode = $asaas->getPixQrCode($charge['id']);
                $charge['pixQrCodeImage'] = $pixQrCode['encodedImage'] ?? null;
                $charge['pixQrCode'] = $pixQrCode['payload'] ?? null;
            }

            // Adicionar linha digitável do boleto se for BOLETO
            if ($data['payment_method'] === 'BOLETO' && isset($charge['id'])) {
                $boletoData = $asaas->getBoletoDigitableLine($charge['id']);
                $charge['identificationField'] = $boletoData['identificationField'] ?? null;
            }

            return response()->json([
                'message' => 'Inscrições criadas com sucesso.',
                'registrations' => $registrations,
                'payment' => [
                    'method' => $data['payment_method'],
                    'amount' => $totalAmount / 100,
                    'pix' => [
                        'qrCodeImage' => $charge['pixQrCodeImage'] ?? null,
                        'payload' => $charge['pixQrCode'] ?? null,
                    ],
                    'boletoUrl' => $charge['bankSlipUrl'] ?? null,
                    'digitableLine' => $charge['identificationField'] ?? null,
                    'creditCard' => [
                        'status' => $charge['status'] ?? null,
                    ],
                ],
            ], 201);
        });
    }

    public function show($id)
    {
        $registration = Registration::with('event')->findOrFail($id);
        return response()->json($registration, 200);
    }

    public function getByQrCode($qrCode)
    {
        $registration = Registration::with('event')
            ->where('qr_code', $qrCode)
            ->firstOrFail();

        return response()->json($registration, 200);
    }

    public function getByPhone($phone)
    {
        $registrations = Registration::with('event')
            ->where('phone', $phone)
            ->get();

        return response()->json($registrations, 200);
    }

    public function getByCpf(Request $request, string $cpf)
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        $perPage = $request->input('per_page', 10);

        $registrations = Registration::with('event')
            ->where('cpf', $cpf)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        if ($registrations->isEmpty()) {
            return response()->json([
                'message' => 'Nenhuma inscrição encontrada para este CPF.'
            ], 404);
        }

        return response()->json($registrations, 200);
    }

    public function markAsPaid($id)
    {
        $registration = Registration::findOrFail($id);
        
        $registration->update([
            'payment_status' => 'paid',
            'payment_method' => $registration->payment_method ?: 'MANUAL',
        ]);
        
        return response()->json([
            'message' => 'Inscrição marcada como paga com sucesso.',
            'registration' => $registration->load('event'),
        ], 200);
    }
}

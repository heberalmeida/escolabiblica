<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;

class AsaasWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->all();

        $paymentId = $event['payment']['id'] ?? null;
        $status    = $event['event'] ?? null;

        if (!$paymentId) {
            return response()->json(['success' => true]);
        }

        $order = Order::where('asaas_payment_id', $paymentId)->first();
        
        // Também buscar registrations com este payment_id
        $registrations = Registration::where('asaas_payment_id', $paymentId)->get();

        // Se não encontrou nem order nem registrations, retornar
        if (!$order && $registrations->isEmpty()) {
            return response()->json(['success' => true]);
        }

        // Processar Order se existir
        if ($order) {
            $oldPayload = $order->gateway_payload ?? [];
            $paymentData = $event['payment'] ?? [];
            $paymentStatus = $paymentData['status'] ?? null;

            $order->gateway_payload = array_merge(
                $oldPayload,
                $paymentData,
                ['event' => $event['event']]
            );

            // Determinar status baseado no evento e no status do pagamento
            switch ($status) {
                case 'PAYMENT_CREATED':
                    // Se o pagamento já está confirmado no momento da criação (ex: cartão de crédito)
                    if ($paymentStatus === 'CONFIRMED') {
                        $order->status = 'paid';
                    } else {
                        $order->status = 'pending';
                    }
                    break;

                case 'PAYMENT_UPDATED':
                    // Verificar o status atual do pagamento
                    if ($paymentStatus === 'CONFIRMED' || $paymentStatus === 'RECEIVED') {
                        $order->status = 'paid';
                    } elseif ($paymentStatus === 'OVERDUE') {
                        $order->status = 'overdue';
                    } elseif (in_array($paymentStatus, ['CANCELLED', 'DELETED', 'REFUNDED'])) {
                        $order->status = $paymentStatus === 'REFUNDED' ? 'refunded' : 'canceled';
                    } else {
                        $order->status = 'pending';
                    }
                    break;

                case 'PAYMENT_RECEIVED':
                case 'PAYMENT_CONFIRMED':
                    $order->status = 'paid';
                    break;

                case 'PAYMENT_OVERDUE':
                    $order->status = 'overdue';
                    break;

                case 'PAYMENT_CANCELLED':
                case 'PAYMENT_DELETED':
                case 'PAYMENT_REPROVED_BY_RISK_ANALYSIS':
                case 'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED':
                    $order->status = 'canceled';
                    break;

                case 'PAYMENT_REFUNDED':
                    $order->status = 'refunded';
                    break;

                case 'PAYMENT_PARTIALLY_REFUNDED':
                    $order->status = 'partially_refunded';
                    break;

                case 'PAYMENT_CHARGEBACK_REQUESTED':
                case 'PAYMENT_CHARGEBACK_DISPUTE':
                    $order->status = 'dispute';
                    break;

                default:
                    Log::warning("Evento Asaas não tratado: {$status}");
            }

            $order->save();
        }

        // Processar Registrations se existirem
        if ($registrations->isNotEmpty()) {
            $paymentData = $event['payment'] ?? [];
            $paymentStatusFromPayload = $paymentData['status'] ?? null;
            $paymentStatus = 'pending';
            
            switch ($status) {
                case 'PAYMENT_CREATED':
                    // Se o pagamento já está confirmado no momento da criação (ex: cartão de crédito)
                    if ($paymentStatusFromPayload === 'CONFIRMED') {
                        $paymentStatus = 'paid';
                    } else {
                        $paymentStatus = 'pending';
                    }
                    break;

                case 'PAYMENT_UPDATED':
                    // Verificar o status atual do pagamento
                    if ($paymentStatusFromPayload === 'CONFIRMED' || $paymentStatusFromPayload === 'RECEIVED') {
                        $paymentStatus = 'paid';
                    } elseif ($paymentStatusFromPayload === 'OVERDUE') {
                        $paymentStatus = 'overdue';
                    } elseif (in_array($paymentStatusFromPayload, ['CANCELLED', 'DELETED', 'REFUNDED'])) {
                        $paymentStatus = $paymentStatusFromPayload === 'REFUNDED' ? 'refunded' : 'canceled';
                    } else {
                        $paymentStatus = 'pending';
                    }
                    break;

                case 'PAYMENT_RECEIVED':
                case 'PAYMENT_CONFIRMED':
                    $paymentStatus = 'paid';
                    break;

                case 'PAYMENT_CANCELLED':
                case 'PAYMENT_DELETED':
                case 'PAYMENT_REPROVED_BY_RISK_ANALYSIS':
                case 'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED':
                    $paymentStatus = 'canceled';
                    break;

                case 'PAYMENT_REFUNDED':
                    $paymentStatus = 'refunded';
                    break;

                default:
                    // Manter status atual para outros eventos
                    break;
            }

            // Atualizar todas as registrations com o novo status
            foreach ($registrations as $registration) {
                $oldPayload = $registration->gateway_payload ?? [];
                
                $registration->gateway_payload = array_merge(
                    $oldPayload,
                    $event['payment'] ?? [],
                    ['event' => $event['event']]
                );
                
                if ($paymentStatus !== 'pending') {
                    $registration->payment_status = $paymentStatus;
                }
                
                $registration->save();
            }
        }

        // Atualizar Firebase apenas se houver uma Order
        if ($order) {
            try {
                $firebase = app(FirebaseService::class);

                $firebase->update("webhooks/orders_{$order->id}", [
                    'id'            => $order->id,
                    'order_number'  => $order->order_number,
                    'buyer_cpf'     => $order->buyer_cpf,
                    'status'        => $order->status,
                    'gateway_payload' => [
                        'status'            => data_get($order->gateway_payload, 'status'),
                        'value'             => data_get($order->gateway_payload, 'value'),
                        'billingType'       => data_get($order->gateway_payload, 'billingType'),
                        'appliedFixTax'     => data_get($order->gateway_payload, 'appliedFixTax'),
                        'appliedPercentTax' => data_get($order->gateway_payload, 'appliedPercentTax'),
                    ],
                    'updated_at'    => now()->toIso8601String(),
                ]);

                $prefix = config('firebase.collection_prefix', '');
                $firebase->update("updates/{$prefix}orders_by_cpf_{$order->buyer_cpf}", [
                    'last_updated' => now()->toIso8601String(),
                    'order_id'     => $order->id,
                    'status'       => $order->status,
                ]);
            } catch (\Throwable $e) {
                Log::error('Erro ao atualizar Firebase para Order', [
                    'order_id' => $order->id ?? null,
                    'payment_id' => $paymentId,
                    'message'  => $e->getMessage(),
                ]);
            }
        }

        // Atualizar Firebase para Registrations se existirem
        if ($registrations->isNotEmpty()) {
            try {
                $firebase = app(FirebaseService::class);
                $prefix = config('firebase.collection_prefix', '');

                foreach ($registrations as $registration) {
                    // Atualizar informações da registration no Firebase
                    $firebase->update("webhooks/registrations_{$registration->id}", [
                        'id'            => $registration->id,
                        'registration_number' => $registration->registration_number,
                        'qr_code'       => $registration->qr_code,
                        'event_id'      => $registration->event_id,
                        'name'          => $registration->name,
                        'phone'         => $registration->phone,
                        'payment_status' => $registration->payment_status,
                        'gateway_payload' => [
                            'status'            => data_get($registration->gateway_payload, 'status'),
                            'value'             => data_get($registration->gateway_payload, 'value'),
                            'billingType'       => data_get($registration->gateway_payload, 'billingType'),
                        ],
                        'updated_at'    => now()->toIso8601String(),
                    ]);

                    // Atualizar notificação de mudança por telefone (se necessário)
                    if ($registration->phone) {
                        $firebase->update("updates/{$prefix}registrations_by_phone_{$registration->phone}", [
                            'last_updated' => now()->toIso8601String(),
                            'registration_id' => $registration->id,
                            'payment_status' => $registration->payment_status,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Erro ao atualizar Firebase para Registrations', [
                    'payment_id' => $paymentId,
                    'registrations_count' => $registrations->count(),
                    'message'  => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}

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

            $order->gateway_payload = array_merge(
                $oldPayload,
                $event['payment'] ?? [],
                ['event' => $event['event']]
            );

            switch ($status) {
                case 'PAYMENT_CREATED':
                case 'PAYMENT_UPDATED':
                    $order->status = 'pending';
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
            $paymentStatus = 'pending';
            
            switch ($status) {
                case 'PAYMENT_CREATED':
                case 'PAYMENT_UPDATED':
                    $paymentStatus = 'pending';
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
            Log::error('Erro ao atualizar Firebase', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;

class AsaasInstallmentService
{
    public function create(array $payload): array
    {
        $resp = Http::withHeaders([
            'access_token' => config('asaas.http.access_token'),
            'Content-Type' => 'application/json',
        ])->post(config('asaas.http.guzzle.base_uri') . 'payments', [
            'customer'         => $payload['customer'],
            'billingType'      => 'CREDIT_CARD',
            'description'      => $payload['description'],
            'dueDate'          => $payload['dueDate'],
            'installmentCount' => $payload['installmentCount'],
            'totalValue'       => $payload['totalValue'],
            'creditCard'       => $payload['creditCard'],
            'creditCardHolderInfo' => $payload['creditCardHolderInfo'],
            'remoteIp'         => $payload['remoteIp'],
        ]);

        if ($resp->failed()) {
            throw new \Exception('Erro ao criar parcelamento: ' . $resp->body());
        }

        return $resp->json();
    }
}

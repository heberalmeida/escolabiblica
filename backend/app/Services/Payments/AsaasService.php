<?php

namespace App\Services\Payments;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Jetimob\Asaas\Facades\Asaas;
use Jetimob\Asaas\Entity\Customer\Customer;
use Jetimob\Asaas\Entity\Charging\Charging;

class AsaasService
{
    public function ensureCustomer(array $data): array
    {
        $cpf = $data['cpf'] ?? null;
        $user = null;
        
        if ($cpf && $cpf !== '00000000000') {
            $user = User::where('cpf', $cpf)->first();
            if ($user && $user->asaas_customer_id) {
                return ['id' => $user->asaas_customer_id];
            }
        }

        if ($cpf && $cpf !== '00000000000') {
            $existing = Http::withHeaders([
                'access_token' => config('asaas.http.access_token'),
            ])->get(config('asaas.http.guzzle.base_uri') . 'customers', [
                'cpfCnpj' => $cpf,
            ]);

            if ($existing->ok() && count($existing['data'] ?? []) > 0) {
                $id = $existing['data'][0]['id'];
                if ($user) {
                    $user->update(['asaas_customer_id' => $id]);
                }
                return ['id' => $id];
            }
        }

        $c = (new Customer())
            ->setName($data['name'])
            ->setCpfCnpj($cpf ?? '00000000000')
            ->setEmail($data['email'] ?? null)
            ->setMobilePhone($data['phone'] ?? null);

        // Adicionar endereço se disponível
        if (!empty($data['postalCode'])) {
            $c->setPostalCode($data['postalCode']);
            if (!empty($data['address'])) {
                $c->setAddress($data['address']);
            }
            if (!empty($data['addressNumber'])) {
                $c->setAddressNumber($data['addressNumber']);
            }
            if (!empty($data['addressComplement'])) {
                $c->setComplement($data['addressComplement']);
            }
            if (!empty($data['province'])) {
                $c->setProvince($data['province']);
            }
            if (!empty($data['city'])) {
                $c->setCity($data['city']);
            }
            if (!empty($data['state'])) {
                $c->setState($data['state']);
            }
        }

        $resp = Asaas::customer()->create($c);

        if ($user) {
            $user->update(['asaas_customer_id' => $resp->getId()]);
        }

        return $resp->toArray();
    }

    public function createCharge(array $payload): array
    {
        $ch = (new Charging())
            ->setCustomer($payload['customer'])
            ->setBillingType($payload['billingType'])
            ->setDescription($payload['description'])
            ->setValue((float) $payload['value']);

        // Sempre definir dueDate se estiver no payload ou se for BOLETO/PIX
        if (isset($payload['dueDate'])) {
            $ch->setDueDate($payload['dueDate']);
        } elseif (in_array($payload['billingType'], ['BOLETO', 'PIX'])) {
            $ch->setDueDate(now()->addDays(1)->format('Y-m-d'));
        }


        if (isset($payload['creditCardToken'])) {
            $ch->setCreditCardToken($payload['creditCardToken']);
            $ch->setRemoteIp($payload['remoteIp'] ?? request()->ip());

            if (($payload['installmentCount'] ?? 1) > 1) {
                $ch->setInstallmentCount($payload['installmentCount']);
                $ch->setInstallmentValue($payload['installmentValue']);
            }
        }

        $resp = Asaas::charging()->create($ch);

        return $resp->toArray();
    }

    public function tokenizeCard(string $customerId, array $cardData): array
    {
        $resp = Http::withHeaders([
            'access_token' => config('asaas.http.access_token'),
            'Content-Type' => 'application/json',
        ])->post(config('asaas.http.guzzle.base_uri') . 'creditCard/tokenize', [
            'customer'   => $customerId,
            'creditCard' => [
                'holderName'  => $cardData['holderName'],
                'number'      => $cardData['number'],
                'expiryMonth' => $cardData['expiryMonth'],
                'expiryYear'  => $cardData['expiryYear'],
                'ccv'         => $cardData['ccv'],
            ],
        ]);

        if ($resp->failed()) {
            throw new \Exception('Erro ao tokenizar cartão: ' . $resp->body());
        }

        return $resp->json();
    }

    public function getChargeById(string $id): array
    {
        $resp = Http::withHeaders([
            'access_token' => config('asaas.http.access_token'),
            'Content-Type' => 'application/json',
        ])->get(config('asaas.http.guzzle.base_uri') . 'payments/' . $id);

        if ($resp->failed()) {
            throw new \Exception('Erro ao consultar cobrança no Asaas: ' . $resp->body());
        }

        return $resp->json();
    }

    public function getPixQrCode(string $paymentId): array
    {
        $resp = Http::withHeaders([
            'access_token' => config('asaas.http.access_token'),
            'Content-Type' => 'application/json',
        ])->get(config('asaas.http.guzzle.base_uri') . "payments/{$paymentId}/pixQrCode");

        if ($resp->failed()) {
            throw new \Exception('Erro ao buscar QR Code PIX: ' . $resp->body());
        }

        return $resp->json();
    }

    public function getBoletoDigitableLine(string $paymentId): array
    {
        $resp = Http::withHeaders([
            'access_token' => config('asaas.http.access_token'),
            'Content-Type' => 'application/json',
        ])->get(config('asaas.http.guzzle.base_uri') . "payments/{$paymentId}/identificationField");

        if ($resp->failed()) {
            throw new \Exception('Erro ao buscar linha digitável do boleto: ' . $resp->body());
        }

        return $resp->json();
    }

    public function simulateInstallments(float $value, int $maxInstallments = 10): array
    {
        $resp = Http::withHeaders([
            'access_token' => config('asaas.http.access_token'),
            'Content-Type' => 'application/json',
        ])->post(config('asaas.http.guzzle.base_uri') . 'payments/simulate', [
            'value' => $value,
            'installmentCount' => $maxInstallments,
            'billingType' => 'CREDIT_CARD',
        ]);

        if ($resp->failed()) {
            throw new \Exception('Erro ao simular parcelamento: ' . $resp->body());
        }

        return $resp->json();
    }
}

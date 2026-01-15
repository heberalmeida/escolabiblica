<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Payments\AsaasService;

class AsaasTokenizeController extends Controller
{
    public function tokenize(Request $request, AsaasService $asaas)
    {
        $data = $request->validate([
            'buyer.name'  => 'required|string|max:255',
            'buyer.cpf'   => 'required|string|max:14',
            'buyer.email' => 'nullable|email',
            'buyer.phone' => 'nullable|string|max:20',
            'card.holderName'  => 'required|string|max:255',
            'card.number'      => 'required|string|min:13|max:19',
            'card.expiryMonth' => 'required|string|size:2',
            'card.expiryYear'  => 'required|string|size:4',
            'card.ccv'         => 'required|string|min:3|max:4',
        ]);

        $customer = $asaas->ensureCustomer([
            'name'  => $data['buyer']['name'],
            'cpf'   => $data['buyer']['cpf'],
            'email' => $data['buyer']['email'] ?? null,
            'phone' => $data['buyer']['phone'] ?? null,
        ]);

        $token = $asaas->tokenizeCard($customer['id'], $data['card']);

        return response()->json([
            'creditCardToken' => $token['creditCardToken'] ?? null,
        ], 201);
    }
}

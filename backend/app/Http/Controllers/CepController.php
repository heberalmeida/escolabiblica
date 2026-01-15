<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CepController extends Controller
{
    public function show($cep)
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return response()->json(['error' => 'CEP inválido'], 422);
        }

        $resp = Http::get("https://viacep.com.br/ws/{$cep}/json/");

        if ($resp->failed() || isset($resp->json()['erro'])) {
            return response()->json(['error' => 'CEP não encontrado'], 404);
        }

        return response()->json($resp->json());
    }
}

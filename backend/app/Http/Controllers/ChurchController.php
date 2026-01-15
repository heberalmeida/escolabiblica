<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChurchController extends Controller
{
    public function index()
    {
        try {
            $response = Http::get('https://api.genesis.admcg.com.br/api/v1/public/cadastro/igrejas');

            if ($response->successful()) {
                $churches = $response->json();
                
                // Agrupar por setor
                $grouped = collect($churches)->groupBy('setor')->map(function ($group) {
                    return $group->map(function ($church) {
                        return [
                            'id' => $church['id'],
                            'nome' => $church['nome'],
                            'setor' => $church['setor'],
                            'subsede' => $church['subsede'],
                            'subsede_name' => $church['subsede_name'] ?? null,
                        ];
                    })->values();
                });

                return response()->json([
                    'churches' => $churches,
                    'grouped_by_sector' => $grouped,
                ], 200);
            }

            return response()->json([
                'message' => 'Erro ao buscar igrejas.',
                'churches' => [],
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar igrejas: ' . $e->getMessage(),
                'churches' => [],
            ], 500);
        }
    }
}

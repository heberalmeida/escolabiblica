<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;

class QRCodeValidationController extends Controller
{
    public function validateByQrCode(Request $request)
    {
        $data = $request->validate([
            'qr_code' => 'required|string',
        ]);

        $registration = Registration::where('qr_code', $data['qr_code'])->first();

        if (!$registration) {
            return response()->json([
                'message' => 'Inscrição não encontrada.',
                'valid' => false,
            ], 404);
        }

        if ($registration->validated) {
            return response()->json([
                'message' => 'Esta inscrição já foi validada anteriormente.',
                'valid' => false,
                'registration' => $registration,
                'validated_at' => $registration->validated_at,
                'validated_by' => $registration->validated_by,
            ], 422);
        }

        $validatedBy = $request->user() ? $request->user()->name : 'Sistema';
        $success = $registration->validate($validatedBy);

        if ($success) {
            return response()->json([
                'message' => 'Inscrição validada com sucesso.',
                'valid' => true,
                'registration' => $registration->load('event'),
            ], 200);
        }

        return response()->json([
            'message' => 'Não foi possível validar a inscrição.',
            'valid' => false,
        ], 422);
    }

    public function validateByName(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'event_id' => 'required|exists:events,id',
        ]);

        $registration = Registration::where('event_id', $data['event_id'])
            ->where('name', 'like', '%' . $data['name'] . '%')
            ->first();

        if (!$registration) {
            return response()->json([
                'message' => 'Inscrição não encontrada.',
                'valid' => false,
            ], 404);
        }

        if ($registration->validated) {
            return response()->json([
                'message' => 'Esta inscrição já foi validada anteriormente.',
                'valid' => false,
                'registration' => $registration,
                'validated_at' => $registration->validated_at,
                'validated_by' => $registration->validated_by,
            ], 422);
        }

        $validatedBy = $request->user() ? $request->user()->name : 'Sistema';
        $success = $registration->validate($validatedBy);

        if ($success) {
            return response()->json([
                'message' => 'Inscrição validada com sucesso.',
                'valid' => true,
                'registration' => $registration->load('event'),
            ], 200);
        }

        return response()->json([
            'message' => 'Não foi possível validar a inscrição.',
            'valid' => false,
        ], 422);
    }

    public function validateByPhone(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'event_id' => 'required|exists:events,id',
        ]);

        $registration = Registration::where('event_id', $data['event_id'])
            ->where('phone', $data['phone'])
            ->first();

        if (!$registration) {
            return response()->json([
                'message' => 'Inscrição não encontrada.',
                'valid' => false,
            ], 404);
        }

        if ($registration->validated) {
            return response()->json([
                'message' => 'Esta inscrição já foi validada anteriormente.',
                'valid' => false,
                'registration' => $registration,
                'validated_at' => $registration->validated_at,
                'validated_by' => $registration->validated_by,
            ], 422);
        }

        $validatedBy = $request->user() ? $request->user()->name : 'Sistema';
        $success = $registration->validate($validatedBy);

        if ($success) {
            return response()->json([
                'message' => 'Inscrição validada com sucesso.',
                'valid' => true,
                'registration' => $registration->load('event'),
            ], 200);
        }

        return response()->json([
            'message' => 'Não foi possível validar a inscrição.',
            'valid' => false,
        ], 422);
    }

    // Buscar múltiplas registrations por nome (não valida) - apenas pagas
    public function searchByName(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'event_id' => 'required|exists:events,id',
        ]);

        // Normalizar o termo de busca: remover espaços extras e converter para minúsculo
        $searchTerm = trim($data['name']);
        $searchTerm = preg_replace('/\s+/', ' ', $searchTerm); // Remove espaços múltiplos
        
        // Dividir em palavras
        $words = explode(' ', $searchTerm);
        $words = array_filter($words, function($word) {
            return strlen(trim($word)) > 0; // Remove palavras vazias
        });
        $words = array_values($words); // Reindexar array

        $query = Registration::where('event_id', $data['event_id'])
            // Filtrar apenas inscrições pagas
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.status') IN ('CONFIRMED', 'RECEIVED')");
            });

        // Se houver múltiplas palavras, buscar onde TODAS as palavras aparecem (independente da ordem)
        if (count($words) > 1) {
            foreach ($words as $word) {
                $query->where('name', 'like', '%' . $word . '%');
            }
        } else {
            // Se for apenas uma palavra, busca simples
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        $registrations = $query->with('event')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($registrations->isEmpty()) {
            return response()->json([
                'message' => 'Nenhuma inscrição paga encontrada.',
                'registrations' => [],
            ], 404);
        }

        return response()->json([
            'message' => 'Inscrições encontradas.',
            'registrations' => $registrations,
            'count' => $registrations->count(),
        ], 200);
    }

    // Buscar múltiplas registrations por telefone (não valida) - apenas pagas
    public function searchByPhone(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'event_id' => 'required|exists:events,id',
        ]);

        // Limpar telefone (remover caracteres não numéricos)
        $phone = preg_replace('/\D/', '', $data['phone']);

        $registrations = Registration::where('event_id', $data['event_id'])
            // Filtrar apenas inscrições pagas
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereRaw("JSON_EXTRACT(gateway_payload, '$.status') IN ('CONFIRMED', 'RECEIVED')");
            })
            ->whereRaw('REPLACE(REPLACE(REPLACE(REPLACE(phone, "(", ""), ")", ""), " ", ""), "-", "") = ?', [$phone])
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($registrations->isEmpty()) {
            return response()->json([
                'message' => 'Nenhuma inscrição paga encontrada.',
                'registrations' => [],
            ], 404);
        }

        return response()->json([
            'message' => 'Inscrições encontradas.',
            'registrations' => $registrations,
            'count' => $registrations->count(),
        ], 200);
    }

    // Validar por número da inscrição (registration_number)
    public function validateByRegistrationNumber(Request $request)
    {
        $data = $request->validate([
            'registration_number' => 'required|string',
        ]);

        // Converter para maiúsculo
        $registrationNumber = strtoupper(trim($data['registration_number']));

        $registration = Registration::where('registration_number', $registrationNumber)
            ->with('event')
            ->first();

        if (!$registration) {
            return response()->json([
                'message' => 'Inscrição não encontrada.',
                'valid' => false,
            ], 404);
        }

        if ($registration->validated) {
            return response()->json([
                'message' => 'Esta inscrição já foi validada anteriormente.',
                'valid' => false,
                'registration' => $registration,
                'validated_at' => $registration->validated_at,
                'validated_by' => $registration->validated_by,
            ], 422);
        }

        $validatedBy = $request->user() ? $request->user()->name : 'Sistema';
        $success = $registration->validate($validatedBy);

        if ($success) {
            return response()->json([
                'message' => 'Inscrição validada com sucesso.',
                'valid' => true,
                'registration' => $registration,
            ], 200);
        }

        return response()->json([
            'message' => 'Não foi possível validar a inscrição.',
            'valid' => false,
        ], 422);
    }
}

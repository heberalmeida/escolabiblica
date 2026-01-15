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
}

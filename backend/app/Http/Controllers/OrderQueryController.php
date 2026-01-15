<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderQueryController extends Controller
{
    public function byCpf(string $cpf, Request $request)
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        $perPage = $request->input('per_page', 6);

        $orders = Order::with(['items.variant.product', 'sector'])
            ->where('buyer_cpf', $cpf)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum pedido encontrado para este CPF.'
            ], 404);
        }

        return response()->json($orders, 200);
    }

    public function byNumber(string $orderNumber)
    {
        $order = Order::with(['items.variant.product', 'sector'])
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Pedido não encontrado.'
            ], 404);
        }

        return response()->json($order, 200);
    }
}

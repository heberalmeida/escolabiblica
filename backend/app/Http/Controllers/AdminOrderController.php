<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrderController extends Controller
{
    /**
     * Lista pedidos
     * - Admin vê todos
     * - Gestor de setor vê apenas os do seu setor
     * GET /api/v1/admin/orders
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Order::with(['items.variant.product', 'sector'])
            ->orderByDesc('created_at');

        if ($user->hasRole('gestor_setor')) {
            $query->where('sector_id', $user->sector_id);
        }

        // Filtros opcionais
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('sector_id')) {
            $query->where('sector_id', $request->sector_id);
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59',
            ]);
        }

        if ($request->has('order_number')) {
            $query->where('order_number', 'like', "%{$request->order_number}%");
        }

        if ($request->has('buyer_name')) {
            $query->where('buyer_name', 'like', "%{$request->buyer_name}%");
        }

        if ($request->has('payment')) {
            $query->whereRaw("UPPER(payment_method) = ?", [strtoupper($request->payment)]);
        }

        $orders = $query->paginate(20);

        return response()->json($orders, 200);
    }

    /**
     * Mostra detalhes de um pedido específico
     * GET /api/v1/admin/orders/{id}
     */
    public function show($id)
    {
        $user = Auth::user();

        $order = Order::with(['items.variant.product', 'sector'])->findOrFail($id);

        if ($user->hasRole('gestor_setor') && $order->sector_id !== $user->sector_id) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        return response()->json($order, 200);
    }

    /**
     * Atualiza status manualmente (admin/gestor)
     * PUT /api/v1/admin/orders/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,paid,canceled,overdue,failed',
        ]);

        $user = Auth::user();

        $order = Order::findOrFail($id);

        if ($user->hasRole('gestor_setor') && $order->sector_id !== $user->sector_id) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        $order->status = $data['status'];
        $order->save();

        return response()->json([
            'message' => 'Status do pedido atualizado com sucesso',
            'order'   => $order,
        ], 200);
    }
}

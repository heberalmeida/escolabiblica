<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $end = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $ordersQuery = Order::whereBetween('created_at', [$start, $end]);

        $totalOrders   = (clone $ordersQuery)->count();
        $paidOrders    = (clone $ordersQuery)->where('status', 'paid')->count();
        $productsCount = Product::count();
        $usersCount    = User::count();

        $latestOrders = (clone $ordersQuery)
            ->with('sector')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $latestProducts = Product::orderByDesc('created_at')
            ->take(5)
            ->get();

        $valuePaid = (clone $ordersQuery)->where('status', 'paid')->get()->sum(fn ($o) => $this->resolveOrderValue($o));
        $valuePending = (clone $ordersQuery)->where('status', 'pending')->get()->sum(fn ($o) => $this->resolveOrderValue($o));
        $valueCanceled = (clone $ordersQuery)->where('status', 'canceled')->get()->sum(fn ($o) => $this->resolveOrderValue($o));
        $valueOverdue = (clone $ordersQuery)->where('status', 'overdue')->get()->sum(fn ($o) => $this->resolveOrderValue($o));
        $valueTotal = (clone $ordersQuery)->get()->sum(fn ($o) => $this->resolveOrderValue($o));

        $byPayment = (clone $ordersQuery)
            ->selectRaw('payment_method, COUNT(*) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return response()->json([
            'stats' => [
                'orders'        => $totalOrders,
                'paid'          => $paidOrders,
                'products'      => $productsCount,
                'users'         => $usersCount,
                'value_paid'    => $valuePaid,
                'value_pending' => $valuePending,
                'value_canceled'=> $valueCanceled,
                'value_overdue' => $valueOverdue,
                'value_total'   => $valueTotal,
                'byPayment'     => $byPayment,
            ],
            'latestOrders'   => $latestOrders,
            'latestProducts' => $latestProducts,
            'period' => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ]
        ]);
    }

    private function resolveOrderValue($order)
    {
        $gp = $order->gateway_payload ?? [];

        if (isset($gp['totalValue'])) {
            return (float) $gp['totalValue'] * 100;
        }

        if (isset($gp['value'])) {
            return (float) $gp['value'] * 100;
        }

        return (float) $order->total_amount;
    }
}

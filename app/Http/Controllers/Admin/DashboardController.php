<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total'      => Order::count(),
            'pending'    => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'done'       => Order::where('status', 'done')
                                 ->whereMonth('updated_at', now()->month)->count(),
        ];

        $recentOrders = Order::with('service')->latest()->limit(8)->get();

        // Chart 1: Order per 7 hari terakhir
        $last7Days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));
        $ordersRaw = Order::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->groupBy('date')
            ->pluck('total', 'date');

        $chartDays = $last7Days->map(fn($d) => [
            'label' => now()->parse($d)->locale('id')->translatedFormat('D, d M'),
            'total' => $ordersRaw->get($d, 0),
        ]);

        // Chart 2: Order per layanan (top 6)
        $ordersByService = Order::selectRaw('services.name as service_name, COUNT(orders.id) as total')
            ->leftJoin('services', 'orders.service_id', '=', 'services.id')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'chartDays', 'ordersByService'));
    }
}

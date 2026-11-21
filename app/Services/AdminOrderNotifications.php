<?php

namespace App\Services;

use App\Models\Order;

class AdminOrderNotifications
{
    public function snapshot(): array
    {
        $pending = Order::where('status', 'pending');

        return [
            'count' => (clone $pending)->count(),
            'items' => $pending->latest()->orderByDesc('id')->limit(5)->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'title' => $order->title,
                'client' => $order->client_name,
                'time' => $order->created_at->locale('id')->diffForHumans(),
                'url' => route('admin.orders.show', $order),
            ])->all(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Money;
use Carbon\CarbonImmutable;

class PublicOrderTracking
{
    public function snapshot(Order $order): array
    {
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $deadline = CarbonImmutable::parse($order->deadline->toDateString(), 'Asia/Jakarta');
        $daysLeft = (int) $today->diffInDays($deadline, false);
        $active = !in_array($order->status, ['done', 'cancelled'], true);

        // Only the fields intended for the holder of this order's tracking link.
        return [
            'status' => $order->status,
            'status_label' => $order->status_label,
            'deadline' => $deadline->toDateString(),
            'deadline_label' => $deadline->locale('id')->translatedFormat('d F Y'),
            'deadline_hint' => !$active ? 'Deadline yang tercatat' : ($daysLeft < 0 ? 'Lewat tenggat · hubungi admin' : ($daysLeft === 0 ? 'Tenggat hari ini' : $daysLeft.' hari lagi')),
            'overdue' => $active && $daysLeft < 0,
            'price_final' => $order->price_final,
            'price_label' => $order->price_final !== null ? Money::rupiah($order->price_final) : 'Menunggu konfirmasi harga',
            // "Mulai dari" estimate from the order form — shown until the final price is agreed.
            'price_estimate_label' => $order->price_estimate !== null ? Money::rupiah($order->price_estimate) : null,
            'admin_notes' => $order->admin_notes,
            'completed_at' => $order->completed_at?->toIso8601String(),
            'completed_label' => $order->completed_at ? $order->completed_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H:i').' WIB' : null,
            'updated_label' => $order->updated_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i').' WIB',
        ];
    }
}

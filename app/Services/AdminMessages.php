<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Support\Str;

/** WhatsApp texts sent to the admin. Uses WhatsApp formatting (*bold*, _italic_). */
class AdminMessages
{
    public function newOrder(Order $order): string
    {
        $phone = Phone::normalize($order->client_phone);
        $answers = collect($order->detail_answers)->map(fn (array $answer) => "   • {$answer['label']}: *{$answer['display']}*")->all();
        $estimate = $order->price_estimate !== null ? Money::rupiah($order->price_estimate).' _(belum final)_' : '-';

        $lines = [
            '🔔 *ORDER BARU MASUK*',
            '━━━━━━━━━━━━━━━',
            "📋 *Kode:* {$order->tracking_token}",
            '📚 *Layanan:* '.($order->service?->name ?? 'Layanan tidak tersedia'),
            ...$answers,
            "📝 *Judul:* {$order->title}",
            "💰 *Estimasi:* {$estimate}",
            '📅 *Deadline:* '.$order->deadline->locale('id')->translatedFormat('d F Y'),
            '💵 *Budget klien:* '.($order->budget ?: 'Belum ditentukan'),
            '',
            "👤 *Klien:* {$order->client_name}",
            "📱 *WhatsApp:* {$order->client_phone}",
            ...($order->client_email ? ["✉️ *Email:* {$order->client_email}"] : []),
            '',
            '🗒️ *Brief:*',
            '_'.Str::limit(trim(preg_replace('/\s+/', ' ', $order->description)), 300).'_',
            '━━━━━━━━━━━━━━━',
            '👉 Hubungi klien untuk konsultasi, lalu isi *harga final* di admin.',
            "💬 *Chat klien:* https://wa.me/{$phone}",
            '🔗 *Buka order:* '.route('admin.orders.show', $order),
        ];

        return implode("\n", $lines);
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Money;

/** WhatsApp texts sent to clients. Kept in one place so wording stays consistent. */
class ClientMessages
{
    private const STATUS_LINES = [
        'processing' => 'Order kamu mulai dikerjakan 🚀',
        'review' => 'Hasil pengerjaan sedang kami review sebelum dikirim 🔍',
        'done' => 'Order kamu sudah selesai! 🎉 Terima kasih sudah mempercayakan ke NativeCuy.',
        'cancelled' => 'Order kamu dibatalkan. Hubungi admin jika ini tidak sesuai.',
        'pending' => 'Order kamu kembali menunggu konfirmasi admin.',
    ];

    public function created(Order $order): string
    {
        $lines = [
            "Halo *{$order->client_name}*! 👋",
            '',
            'Order kamu di *NativeCuy* sudah kami terima 🦉',
            '',
            "📋 *Kode tracking:* {$order->tracking_token}",
            '📚 *Layanan:* '.($order->service?->name ?? '-'),
            ...$this->answerLines($order),
            '📅 *Deadline diajukan:* '.$this->date($order),
            $this->priceLine($order),
            '',
            $order->price_final === null
                ? 'Admin akan segera menghubungi kamu untuk konsultasi dan menyepakati harga final.'
                : 'Harga final sudah disepakati. Admin akan mengabari progresnya di sini.',
            ...$this->footer($order),
        ];

        return implode("\n", $lines);
    }

    public function updated(Order $order, array $events): string
    {
        $lines = ["Halo *{$order->client_name}*! 👋", '', "Ada update untuk order *{$order->tracking_token}* ({$order->title}):", ''];

        if (! empty($events['price']) && $order->price_final !== null) {
            $lines[] = '💰 *Harga final disepakati:* '.Money::rupiah($order->price_final);
        }
        if (! empty($events['status'])) {
            $lines[] = '📌 *Status:* '.$order->status_label;
            $lines[] = self::STATUS_LINES[$events['status']] ?? '';
        }
        $lines[] = '📅 *Deadline:* '.$this->date($order);

        if ($order->admin_notes) {
            array_push($lines, '', '💬 *Pesan dari admin:*', $order->admin_notes);
        }

        return implode("\n", array_merge($lines, $this->footer($order)));
    }

    /** Short description for the order history, e.g. "harga final & status Sedang Dikerjakan". */
    public function summary(array $events): string
    {
        $parts = [];
        if (! empty($events['price'])) {
            $parts[] = 'harga final';
        }
        if (! empty($events['status'])) {
            $parts[] = 'status '.(Order::STATUSES[$events['status']]['label'] ?? $events['status']);
        }

        return $parts ? implode(' & ', $parts) : 'update order';
    }

    private function answerLines(Order $order): array
    {
        return collect($order->detail_answers)->map(fn (array $answer) => "   • {$answer['label']}: {$answer['display']}")->all();
    }

    private function priceLine(Order $order): string
    {
        if ($order->price_final !== null) {
            return '💰 *Harga final:* '.Money::rupiah($order->price_final);
        }

        return $order->price_estimate !== null
            ? '💰 *Estimasi mulai dari:* '.Money::rupiah($order->price_estimate).' _(belum harga final)_'
            : '💰 *Harga:* disepakati saat konsultasi';
    }

    private function date(Order $order): string
    {
        return $order->deadline->locale('id')->translatedFormat('d F Y');
    }

    private function footer(Order $order): array
    {
        return [
            '',
            '🔗 Pantau progres: '.route('order.track', $order->tracking_token),
            '_Masukkan 4 digit terakhir nomor WhatsApp ini saat diminta._',
            '',
            'Ada pertanyaan? Balas pesan ini ya.',
            '— *NativeCuy*',
        ];
    }
}

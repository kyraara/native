<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Admin-controlled switches for order notifications (stored in the settings table).
 * The gateway itself (Fonnte token, endpoint) stays in .env.
 */
class NotificationSettings
{
    public const TOGGLES = [
        'notify_admin_new_order' => ['label' => 'Order baru masuk', 'audience' => 'admin', 'help' => 'Pesan ke nomor admin setiap ada order dari website.'],
        'notify_client_created' => ['label' => 'Order diterima', 'audience' => 'client', 'help' => 'Klien menerima kode tracking, ringkasan, dan estimasi.'],
        'notify_client_price' => ['label' => 'Harga final disepakati', 'audience' => 'client', 'help' => 'Dikirim saat admin mengisi atau mengubah harga final.'],
        'notify_client_status' => ['label' => 'Perubahan status', 'audience' => 'client', 'help' => 'Dikerjakan, review, selesai, atau dibatalkan.'],
    ];

    public static function enabled(string $key): bool
    {
        return Setting::get($key, '1') === '1';
    }

    /** Admin WhatsApp number: the setting wins, .env FONNTE_TARGET is the fallback. */
    public static function adminTarget(): string
    {
        $value = (string) Setting::get('notify_admin_target', '');

        return $value !== '' ? $value : (string) config('services.order_notifications.whatsapp.target');
    }

    /** Fonnte is switched on in .env and has a token. */
    public static function whatsAppReady(): bool
    {
        return (bool) config('services.order_notifications.whatsapp.enabled')
            && filled(config('services.order_notifications.whatsapp.token'));
    }
}

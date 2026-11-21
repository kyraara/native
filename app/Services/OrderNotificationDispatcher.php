<?php

namespace App\Services;

use App\Jobs\SendNewOrderEmail;
use App\Jobs\SendNewOrderWhatsApp;
use App\Models\Order;
use App\Support\NotificationSettings;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderNotificationDispatcher
{
    public function dispatch(Order $order): void
    {
        if ($this->emailIsConfigured()) {
            $this->dispatchSafely(new SendNewOrderEmail($order->id), 'email', $order);
        }

        if ($this->whatsAppIsConfigured()) {
            $this->dispatchSafely(new SendNewOrderWhatsApp($order->id), 'whatsapp', $order);
        }
    }

    private function emailIsConfigured(): bool
    {
        return (bool) config('services.order_notifications.email.enabled')
            && filled(config('services.order_notifications.email.recipient'));
    }

    private function whatsAppIsConfigured(): bool
    {
        return NotificationSettings::whatsAppReady()
            && NotificationSettings::enabled('notify_admin_new_order')
            && filled(NotificationSettings::adminTarget());
    }

    private function dispatchSafely(object $job, string $channel, Order $order): void
    {
        try {
            Bus::dispatch($job);
        } catch (Throwable $exception) {
            Log::error('Gagal memasukkan notifikasi order ke antrean.', [
                'order_id' => $order->id,
                'channel' => $channel,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

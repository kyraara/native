<?php

namespace App\Services;

use App\Jobs\SendClientWhatsApp;
use App\Models\Order;
use App\Support\NotificationSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Decides whether a client WhatsApp is due and queues it. Never breaks the request that triggered it. */
class ClientNotifier
{
    public function orderReceived(Order $order): bool
    {
        if (! NotificationSettings::whatsAppReady() || ! NotificationSettings::enabled('notify_client_created')) {
            return false;
        }

        return $this->queue(new SendClientWhatsApp($order->id, 'created'), $order);
    }

    /**
     * @param  string|null  $previousStatus  status before the change (null = unchanged)
     * @param  bool  $priceAgreed  price_final was set or changed to a value
     */
    public function orderUpdated(Order $order, ?string $previousStatus, bool $priceAgreed): bool
    {
        if (! NotificationSettings::whatsAppReady()) {
            return false;
        }

        $events = [];
        if ($priceAgreed && $order->price_final !== null && NotificationSettings::enabled('notify_client_price')) {
            $events['price'] = true;
        }
        if ($previousStatus !== null && $previousStatus !== $order->status && NotificationSettings::enabled('notify_client_status')) {
            $events['status'] = $order->status;
        }

        return $events !== [] && $this->queue(new SendClientWhatsApp($order->id, 'updated', $events), $order);
    }

    private function queue(SendClientWhatsApp $job, Order $order): bool
    {
        try {
            dispatch($job);

            return true;
        } catch (Throwable $exception) {
            Log::error('Notifikasi WhatsApp klien gagal dimasukkan ke antrean.', ['order_id' => $order->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }
}

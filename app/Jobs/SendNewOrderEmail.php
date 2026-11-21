<?php

namespace App\Jobs;

use App\Mail\NewOrderAdminMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNewOrderEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $orderId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $order = Order::with('service')->findOrFail($this->orderId);
        $recipient = (string) config('services.order_notifications.email.recipient');

        Mail::to($recipient)->send(new NewOrderAdminMail($order));

        Log::info('Notifikasi email order terkirim.', [
            'order_id' => $order->id,
            'channel' => 'email',
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Notifikasi email order gagal setelah semua percobaan.', [
            'order_id' => $this->orderId,
            'channel' => 'email',
            'error' => $exception->getMessage(),
        ]);
    }
}

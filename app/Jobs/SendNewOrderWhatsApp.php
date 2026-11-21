<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsWhatsAppOnce;
use App\Models\Order;
use App\Services\AdminMessages;
use App\Services\FonnteClient;
use App\Support\NotificationSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNewOrderWhatsApp implements ShouldQueue
{
    use Queueable, SendsWhatsAppOnce;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $orderId)
    {
        $this->assignMessageKey();
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(FonnteClient $fonnte, ?AdminMessages $messages = null): void
    {
        if ($this->alreadySent()) {
            return;
        }

        $order = Order::with('service')->find($this->orderId);

        if (! $order) {
            return;
        }

        $fonnte->sendText(NotificationSettings::adminTarget(), ($messages ?? app(AdminMessages::class))->newOrder($order));
        $this->markSent();

        $this->afterSent(fn () => Log::info('Notifikasi WhatsApp order terkirim.', ['order_id' => $order->id, 'channel' => 'whatsapp']));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Notifikasi WhatsApp order gagal setelah semua percobaan.', [
            'order_id' => $this->orderId,
            'channel' => 'whatsapp',
            'error' => $exception->getMessage(),
        ]);
    }
}

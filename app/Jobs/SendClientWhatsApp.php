<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsWhatsAppOnce;
use App\Models\Order;
use App\Services\ClientMessages;
use App\Services\FonnteClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/** WhatsApp message to the client about their order. The text is built at send time from fresh data. */
class SendClientWhatsApp implements ShouldQueue
{
    use Queueable, SendsWhatsAppOnce;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  string  $kind  "created" or "updated"
     * @param  array  $events  for "updated": ['status' => 'processing', 'price' => true]
     */
    public function __construct(public int $orderId, public string $kind, public array $events = [])
    {
        $this->assignMessageKey();
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(FonnteClient $fonnte, ClientMessages $messages): void
    {
        if ($this->alreadySent()) {
            return; // A previous attempt already delivered this message.
        }

        $order = Order::with('service')->find($this->orderId);

        if (! $order) {
            return; // Order deleted while the message was queued.
        }

        $text = $this->kind === 'created' ? $messages->created($order) : $messages->updated($order, $this->events);

        // Only this call may throw (and be retried): the message has not been sent yet.
        $fonnte->sendText($order->client_phone, $text);
        $this->markSent();

        $this->afterSent(function () use ($order, $messages) {
            $order->logActivity('notification', 'WhatsApp terkirim ke klien: '.($this->kind === 'created' ? 'order diterima' : $messages->summary($this->events)).'.');
            Log::info('Notifikasi WhatsApp klien terkirim.', ['order_id' => $order->id, 'kind' => $this->kind]);
        });
    }

    public function failed(Throwable $exception): void
    {
        $this->afterSent(fn () => Order::find($this->orderId)?->logActivity('notification_failed', 'WhatsApp ke klien gagal dikirim: '.str($exception->getMessage())->limit(120).'.'));
        Log::error('Notifikasi WhatsApp klien gagal setelah semua percobaan.', [
            'order_id' => $this->orderId,
            'kind' => $this->kind,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * A WhatsApp job may be retried by the queue. Once a message has actually been sent,
 * a retry must never send it again — so every message carries a key that is marked as
 * sent right after Fonnte accepts it, and bookkeeping after sending can never throw.
 */
trait SendsWhatsAppOnce
{
    /** Unique per dispatched message; older queued payloads without a key still work. */
    public ?string $messageKey = null;

    protected function assignMessageKey(): void
    {
        $this->messageKey = (string) Str::uuid();
    }

    protected function alreadySent(): bool
    {
        return $this->messageKey !== null && Cache::has($this->sentCacheKey());
    }

    protected function markSent(): void
    {
        if ($this->messageKey === null) {
            return;
        }

        try {
            Cache::put($this->sentCacheKey(), true, now()->addDays(14));
        } catch (Throwable $exception) {
            Log::warning('Penanda WhatsApp terkirim gagal disimpan.', ['key' => $this->messageKey, 'error' => $exception->getMessage()]);
        }
    }

    /** Run follow-up work (history, logs) without ever failing the job after the message went out. */
    protected function afterSent(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            Log::warning('Pencatatan setelah WhatsApp terkirim gagal, pesan tidak dikirim ulang.', ['key' => $this->messageKey, 'error' => $exception->getMessage()]);
        }
    }

    private function sentCacheKey(): string
    {
        return 'whatsapp-sent:'.$this->messageKey;
    }
}

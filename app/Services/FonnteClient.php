<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FonnteClient
{
    public function sendText(string $target, string $message): void
    {
        $response = $this->request()->post(
            (string) config('services.order_notifications.whatsapp.endpoint'),
            [
                'target' => $target,
                'message' => $message,
            ],
        );

        $response->throw();

        if ($response->json('status') !== true) {
            throw new RuntimeException(
                (string) ($response->json('reason') ?: 'Fonnte menolak pengiriman pesan.'),
            );
        }
    }

    private function request(): PendingRequest
    {
        return Http::asForm()
            ->withHeaders([
                'Authorization' => (string) config('services.order_notifications.whatsapp.token'),
            ])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15);
    }
}

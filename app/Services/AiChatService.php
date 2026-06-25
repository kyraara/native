<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    public function reply(array $history, string $userMessage): string
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            return 'Maaf, AI Customer Service belum aktif. Silakan hubungi admin via WA ya 🙏';
        }

        $primaryModel = config('services.gemini.model', 'gemini-2.5-flash-lite');
        $fallback     = ['gemini-2.5-flash', 'gemini-2.0-flash'];
        $modelsToTry  = array_values(array_unique(array_merge([$primaryModel], $fallback)));

        $payload = $this->buildPayload($history, $userMessage);

        foreach ($modelsToTry as $i => $model) {
            $result = $this->callModel($apiKey, $model, $payload);

            if ($result['ok']) {
                return $result['text'];
            }

            // Hanya fallback ke model lain kalau error 503 (overloaded) atau 429 (quota)
            $status = $result['status'] ?? 0;
            if (! in_array($status, [429, 503], true)) {
                break;
            }
            Log::info("Gemini fallback: {$model} returned {$status}, trying next model");
        }

        return 'Waduh, server AI lagi sibuk banget nih. Coba lagi sebentar ya, atau langsung chat admin via WA aja: wa.me/' . \App\Models\Setting::get('wa_number', '6281234567890') . ' 🙏';
    }

    protected function buildPayload(array $history, string $userMessage): array
    {
        $contents = [];
        foreach ($history as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $text = trim((string) ($msg['content'] ?? ''));
            if ($text === '') continue;
            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        return [
            'system_instruction' => [
                'parts' => [['text' => $this->buildSystemPrompt()]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature'     => 0.6,
                'maxOutputTokens' => 600,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ],
        ];
    }

    protected function callModel(string $apiKey, string $model, array $payload): array
    {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $response = Http::timeout(30)
                ->retry(2, 800, function ($exception, $request) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                }, throw: false)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post($endpoint, $payload);

            if (! $response->successful()) {
                Log::warning('Gemini API non-200', [
                    'model'  => $model,
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 1000),
                ]);
                return ['ok' => false, 'status' => $response->status()];
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! is_string($text) || trim($text) === '') {
                return ['ok' => true, 'text' => 'Hmm, gw belum bisa jawab itu. Bisa coba tanya yg lain seputar layanan NativeCuy?'];
            }

            return ['ok' => true, 'text' => trim($text)];
        } catch (\Throwable $e) {
            Log::error('Gemini API error', ['model' => $model, 'error' => $e->getMessage()]);
            return ['ok' => false, 'status' => 0];
        }
    }

    protected function buildSystemPrompt(): string
    {
        return Cache::remember('ai_cs_system_prompt', 300, function () {
            $services = Service::active()->get(['name', 'description', 'base_price']);
            $wa       = Setting::get('wa_number', '6281234567890');
            $email    = Setting::get('email', 'order@nativecuy.id');
            $ig       = Setting::get('instagram', '@nativecuy.id');

            $serviceList = $services->map(function ($s) {
                $price = 'Rp ' . number_format((float) $s->base_price, 0, ',', '.');
                return "- {$s->name} (mulai {$price}): {$s->description}";
            })->implode("\n");

            $statusList = collect(Order::STATUSES)->map(fn($v, $k) => "- {$k}: {$v['label']}")->implode("\n");

            return <<<PROMPT
Lo adalah "Cuy-AI", asisten Customer Service resmi dari **NativeCuy** — platform jasa joki tugas profesional di Indonesia.

# IDENTITAS & GAYA
- Nama lo: Cuy-AI (maskot owl 🦉 NativeCuy)
- Bahasa: Indonesia santai (boleh pakai "gw/lo" atau "aku/kamu" mengikuti gaya user), ramah, tapi profesional
- Jawaban: singkat, padat, langsung ke poin. Maksimal 4-5 kalimat kecuali user minta detail
- Gunakan emoji sewajarnya (🦉 ✨ ✅), jangan berlebihan
- JANGAN sebut diri lo sebagai "AI Google" / "Gemini" / "language model". Lo adalah Cuy-AI.

# RUANG LINGKUP — SANGAT PENTING
Lo HANYA boleh jawab pertanyaan seputar:
1. Layanan NativeCuy (jenis joki, harga, proses)
2. Cara order, cara tracking, status order
3. Kebijakan: revisi, anti-plagiarisme, deadline, pembayaran
4. Info kontak & jam operasional

JANGAN PERNAH:
- Bantu kerjain tugas user langsung (nulis essay, makalah, kode, dll). Arahkan ke form order.
- Jawab pertanyaan di luar topik NativeCuy (politik, gosip, ngoding random, curhat, matematika, dll)
- Bikin janji harga pasti — harga final ditentukan admin setelah review brief
- Ngarang fitur/promo yg gak ada di info di bawah

Kalau user tanya di luar topik, tolak sopan: "Maaf, gw cuma bisa bantu seputar layanan NativeCuy aja ya. Ada yg mau ditanyain soal joki tugas/laporan/PPT/web?"

# DATA LAYANAN (LIVE)
{$serviceList}

Catatan: harga di atas harga MULAI. Harga final tergantung kompleksitas, deadline, dan jumlah halaman/fitur — admin akan kasih quote setelah brief dikirim.

# CARA ORDER
1. Klik tombol "Order Sekarang" / kunjungi halaman /order
2. Isi form 4 langkah: Pilih Layanan → Detail Tugas → Kontak → Konfirmasi
3. Upload file referensi (opsional)
4. Submit → dapet **token tracking**
5. Admin kontak dalam <1 jam (jam kerja) untuk quote harga
6. Setelah deal, mulai pengerjaan

# CARA TRACKING ORDER
- Kunjungi /track lalu masukin token tracking
- Atau langsung /track/{token}
- Status order:
{$statusList}

# KEUNGGULAN
- ✅ Anti plagiarisme (kerja manual, bukan AI mentah)
- ✅ Revisi gratis (dalam batas wajar)
- ✅ Fast respon, deadline-friendly
- ✅ Terpercaya sejak 2022, 500+ order selesai

# KONTAK & HANDOFF
- WhatsApp: wa.me/{$wa} (utama, fast respon)
- Email: {$email}
- Instagram: {$ig}

KAPAN ARAHKAN KE WA:
- User minta nego harga / quote spesifik
- User butuh konsultasi panjang
- User komplain / urgent
- Pertanyaan teknis yg lo gak tau jawabannya
Format ajakan WA: "Mending langsung chat admin di WA aja ya: wa.me/{$wa} 🦉"

# ATURAN OUTPUT
- JANGAN tampilkan markdown heading (#, ##) di chat
- Boleh pakai bullet "-" atau "•" untuk list pendek
- Link tulis polos (wa.me/xxx atau /order), jangan format markdown link
- Selalu helpful tapi tetap di koridor topik NativeCuy
PROMPT;
        });
    }
}

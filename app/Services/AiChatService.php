<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Money;
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

            return ['ok' => true, 'text' => $this->tidy($text)];
        } catch (\Throwable $e) {
            Log::error('Gemini API error', ['model' => $model, 'error' => $e->getMessage()]);
            return ['ok' => false, 'status' => 0];
        }
    }

    /**
     * The chat widget renders plain text with **bold** and links only:
     * turn markdown bullets into "•" and drop headings the model may still produce.
     */
    public function tidy(string $text): string
    {
        $lines = array_map(function (string $line): string {
            $line = preg_replace('/^\s{0,3}#{1,6}\s+/', '', $line);
            return preg_replace('/^(\s*)[*\-]\s+/', '$1• ', $line);
        }, preg_split('/\R/', trim($text)));

        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }

    /** Cache key for the system prompt; cleared whenever services, majors, or settings change. */
    public const PROMPT_CACHE_KEY = 'ai_cs_system_prompt_v2';

    public static function forgetPrompt(): void
    {
        Cache::forget(self::PROMPT_CACHE_KEY);
    }

    protected function buildSystemPrompt(): string
    {
        return Cache::remember(self::PROMPT_CACHE_KEY, 600, function () {
            $services = Service::active()->with(['majors' => fn ($query) => $query->where('is_active', true)])->get();
            $wa       = Setting::get('wa_number', '6281234567890');
            $email    = Setting::get('email', 'order@nativecuy.id');
            $ig       = Setting::get('instagram', '@nativecuy.id');

            $serviceList = $services->map(fn (Service $service) => $this->describeService($service))->implode("\n\n");
            $statusList = collect(Order::STATUSES)->map(fn ($v, $k) => "- {$v['label']}")->implode("\n");

            return <<<PROMPT
Lo adalah "Cuy-AI", asisten Customer Service resmi dari **NativeCuy** — jasa bantuan tugas akademik & digital di Indonesia.

# IDENTITAS & GAYA
- Nama lo: Cuy-AI (maskot owl 🦉 NativeCuy)
- Bahasa Indonesia santai, ramah, tetap profesional. Ikuti gaya user ("gw/lo" atau "aku/kamu")
- Jawaban singkat & langsung ke poin: maksimal 4–6 kalimat atau list pendek, kecuali user minta detail
- Emoji secukupnya (🦉 ✨ ✅)
- JANGAN sebut diri lo "AI Google" / "Gemini" / "language model". Lo adalah Cuy-AI.

# RUANG LINGKUP — SANGAT PENTING
Lo HANYA menjawab seputar:
1. Layanan NativeCuy: jenis layanan, pilihan, daftar harga, proses
2. Cara order, isi form, cara tracking, arti status order
3. Kebijakan: harga final, revisi, deadline, pembayaran (arahkan detailnya ke admin)
4. Kontak admin

JANGAN PERNAH:
- Mengerjakan tugas user (menulis essay, makalah, kode, jawaban soal, dll). Arahkan ke form order.
- Menjawab topik di luar NativeCuy. Tolak sopan lalu tawarkan bantuan seputar layanan.
- Mengarang layanan, pilihan, harga, diskon, atau promo yang tidak ada di DATA LAYANAN.
- Menjanjikan harga final atau deadline pasti.
- Meminta nomor WhatsApp, 4 digit verifikasi, atau data pribadi di chat.

# HARGA: ESTIMASI vs HARGA FINAL (WAJIB DIPAHAMI)
- Semua harga di bawah adalah harga **mulai dari** (estimasi), BUKAN harga final.
- Cara hitung estimasi di form order: harga dasar dari pilihan utama (mis. peringkat SINTA / paket) + biaya tambahan yang dipilih (+Rp) + (jumlah × harga per unit, mis. per slide/halaman).
- Lo boleh menyebut harga per pilihan dari DATA LAYANAN dan memberi contoh perhitungan SEDERHANA, selalu dengan kata "estimasi mulai dari".
- Untuk kombinasi rumit, arahkan ke form order: ESTIMASI otomatis muncul di ringkasan saat klien memilih. Form order TIDAK menampilkan harga final.
- Harga final disepakati bersama admin saat konsultasi setelah brief dikirim, lalu tampil di halaman tracking sebagai "Harga final".
- Nego harga, diskon, atau quote pasti → arahkan ke WA admin.

# DATA LAYANAN (LIVE dari katalog)
{$serviceList}

# CARA ORDER
1. Buka /order (atau link layanan langsung, mis. /order?service=ID dari DATA LAYANAN di atas)
2. Langkah 1 — pilih layanan, isi detail khusus layanan (pilihan seperti di DATA LAYANAN), judul, instruksi, deadline, lampiran opsional
3. Langkah 2 — isi nama, email, dan nomor WhatsApp aktif
4. Langkah 3 — tinjau lalu kirim. Klien langsung dapat **kode tracking** (juga dikirim ke WhatsApp klien)
5. Admin menghubungi klien untuk konsultasi dan menyepakati harga final & deadline
6. Setelah harga final disepakati, order mulai dikerjakan. Klien dikabari lewat WhatsApp saat harga final ditetapkan dan saat status berubah

# CARA TRACKING ORDER
- Buka /track, masukkan kode tracking + 4 digit terakhir nomor WhatsApp pemesan
- Di tracking terlihat: status, deadline terbaru, estimasi awal atau harga final, detail layanan, dan pesan admin
- Verifikasi berlaku 30 menit. Jangan minta kode/nomor di chat, arahkan ke /track
- Urutan status:
{$statusList}

# KONTAK & HANDOFF
- WhatsApp admin: wa.me/{$wa} (utama, respon cepat)
- Email: {$email}
- Instagram: {$ig}
Arahkan ke WA jika: nego/quote pasti, konsultasi panjang, komplain, urgent, pembayaran, atau pertanyaan yang tidak ada di data.
Format: "Langsung chat admin di WA aja ya: wa.me/{$wa} 🦉"

# ATURAN OUTPUT
- JANGAN pakai markdown heading (#) atau tabel
- Boleh **tebal**. Untuk list pendek pakai bullet "•" di awal baris (jangan "*")
- Tulis link polos: wa.me/xxx, /order, /order?service=ID, /track (jangan format markdown link)
- Format rupiah: Rp 1.250.000
PROMPT;
        });
    }

    /** One service block for the prompt: starting price, link, majors, and every priced option. */
    protected function describeService(Service $service): string
    {
        $lines = [
            "▸ LAYANAN: {$service->name} — mulai ".Money::rupiah($service->display_price)." · link order: /order?service={$service->id}",
            "Deskripsi: {$service->description}",
        ];

        if ($service->majors->isNotEmpty()) {
            $lines[] = 'Untuk jurusan: '.$service->majors->pluck('name')->join(', ');
        }

        $fields = $service->formSchema();
        if ($fields === []) {
            $lines[] = 'Form: hanya form umum (judul, instruksi, deadline, lampiran). Harga mulai '.Money::rupiah($service->display_price).'.';

            return implode("\n", $lines);
        }

        $lines[] = 'Isian form & harga:';
        foreach ($fields as $field) {
            $flags = array_filter([
                $field['required'] ? 'wajib' : 'opsional',
                $field['show_if'] ? 'muncul jika '.$this->conditionLabel($fields, $field['show_if']) : null,
                match ($field['pricing']) {
                    'base' => 'menentukan harga dasar',
                    'add' => 'biaya tambahan',
                    'per_unit' => 'dihitung per '.($field['unit_label'] ?: 'unit'),
                    default => null,
                },
            ]);

            $options = match (true) {
                $field['type'] === 'number' && $field['pricing'] === 'per_unit'
                    => [Money::rupiah($field['unit_price']).' per '.($field['unit_label'] ?: 'unit')
                        .($field['min'] !== null ? ", minimal {$field['min']}" : '')
                        .($field['max'] !== null ? ", maksimal {$field['max']}" : '')],
                $field['options'] !== [] => collect($field['options'])->map(function (array $option) use ($field) {
                    if ($field['pricing'] === 'none' || $option['price'] === null) {
                        return $option['label'];
                    }
                    if ($field['pricing'] === 'base') {
                        return $option['label'].' = '.($option['price'] > 0 ? Money::rupiah($option['price']) : 'harga mengikuti pilihan berikutnya');
                    }

                    return $option['label'].($option['price'] > 0 ? ' +'.Money::rupiah($option['price']) : ' (tanpa biaya tambahan)');
                })->all(),
                default => [],
            };

            $lines[] = "- {$field['label']} (".implode(', ', $flags).')'.($options ? ': '.implode('; ', $options) : '');
        }

        return implode("\n", $lines);
    }

    private function conditionLabel(array $fields, array $condition): string
    {
        $source = collect($fields)->firstWhere('key', $condition['field']);
        $labels = collect($source['options'] ?? [])->whereIn('value', $condition['values'])->pluck('label')->join(' / ');

        return ($source['label'] ?? $condition['field']).' = '.($labels ?: implode(' / ', $condition['values']));
    }
}

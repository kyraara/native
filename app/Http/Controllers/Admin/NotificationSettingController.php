<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\FonnteClient;
use App\Support\NotificationSettings;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class NotificationSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.notifications', [
            'toggles' => collect(NotificationSettings::TOGGLES)->map(fn (array $meta, string $key) => $meta + ['enabled' => NotificationSettings::enabled($key)]),
            'ready' => NotificationSettings::whatsAppReady(),
            'switchedOn' => (bool) config('services.order_notifications.whatsapp.enabled'),
            'hasToken' => filled(config('services.order_notifications.whatsapp.token')),
            'adminTarget' => (string) Setting::get('notify_admin_target', ''),
            'envTarget' => (string) config('services.order_notifications.whatsapp.target'),
            'queue' => $this->queueHealth(),
            'email' => [
                'enabled' => (bool) config('services.order_notifications.email.enabled'),
                'recipient' => (string) config('services.order_notifications.email.recipient'),
                'mailer' => (string) config('mail.default'),
                'host' => (string) config('mail.mailers.smtp.host'),
                'from' => (string) config('mail.from.address'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Normalise "0812…" to "62812…"; keep unusable input as typed so validation rejects it instead of clearing the number.
        $raw = (string) $request->input('notify_admin_target', '');
        $normalized = Phone::normalize($raw);
        $request->merge(['notify_admin_target' => trim($raw) === '' ? '' : ($normalized !== '' ? $normalized : $raw)]);
        $request->validate([
            'notify_admin_target' => ['nullable', 'regex:/^62[0-9]{8,13}$/'],
        ], [
            'notify_admin_target.regex' => 'Nomor WhatsApp admin harus diawali 08 atau 62.',
        ]);

        foreach (array_keys(NotificationSettings::TOGGLES) as $key) {
            Setting::set($key, $request->boolean($key) ? '1' : '0');
        }
        Setting::set('notify_admin_target', (string) $request->input('notify_admin_target'));

        return back()->with('success', 'Pengaturan notifikasi disimpan.');
    }

    /** Sends a test message right away (not queued) so the admin sees the result immediately. */
    public function test(FonnteClient $fonnte): RedirectResponse
    {
        if (! NotificationSettings::whatsAppReady()) {
            return back()->with('error', 'WhatsApp belum aktif. Isi FONNTE_TOKEN dan ORDER_WHATSAPP_NOTIFICATION_ENABLED=true di file .env.');
        }

        $target = NotificationSettings::adminTarget();
        if ($target === '') {
            return back()->with('error', 'Isi nomor WhatsApp admin terlebih dahulu.');
        }

        try {
            $fonnte->sendText($target, "✅ Tes notifikasi NativeCuy berhasil.\nPesan order baru dan update klien akan dikirim lewat nomor ini.");
        } catch (Throwable $exception) {
            return back()->with('error', 'Pesan uji gagal: '.str($exception->getMessage())->limit(160));
        }

        return back()->with('success', "Pesan uji terkirim ke {$target}. Cek WhatsApp admin.");
    }

    /** Sends a test email right away (not queued) and shows the mail server's answer. */
    public function testEmail(): RedirectResponse
    {
        $recipient = (string) config('services.order_notifications.email.recipient');

        if ($recipient === '') {
            return back()->with('error', 'Isi ORDER_NOTIFICATION_EMAIL di file .env terlebih dahulu.');
        }

        try {
            Mail::raw("Tes email NativeCuy berhasil.\nNotifikasi order baru akan dikirim ke alamat ini.", function ($message) use ($recipient) {
                $message->to($recipient)->subject('Tes notifikasi email NativeCuy');
            });
        } catch (Throwable $exception) {
            $reason = str($exception->getMessage())->limit(220);
            $hint = str_contains(strtolower($exception->getMessage()), 'authenticate')
                ? ' Username/SMTP key ditolak server email. Untuk Brevo, buat SMTP key baru di menu SMTP & API lalu isi MAIL_USERNAME dan MAIL_PASSWORD.'
                : '';

            return back()->with('error', "Email uji gagal: {$reason}.{$hint}");
        }

        if (config('mail.default') === 'log') {
            return back()->with('error', 'MAIL_MAILER=log: email hanya ditulis ke storage/logs, tidak benar-benar dikirim.');
        }

        return back()->with('success', "Email uji terkirim ke {$recipient}. Cek inbox atau folder spam.");
    }

    /** With the database queue, messages only leave when `php artisan queue:work` runs. */
    private function queueHealth(): array
    {
        $driver = (string) config('queue.default');

        if ($driver !== 'database' || ! Schema::hasTable('jobs')) {
            return ['driver' => $driver, 'waiting' => 0, 'stuck' => false, 'failed' => $this->failedCount()];
        }

        $waiting = DB::table('jobs')->count();
        $oldest = DB::table('jobs')->min('available_at');
        $stuck = $oldest !== null && CarbonImmutable::createFromTimestamp((int) $oldest)->lt(now()->subMinutes(3));

        return ['driver' => $driver, 'waiting' => $waiting, 'stuck' => $stuck, 'failed' => $this->failedCount()];
    }

    private function failedCount(): int
    {
        return Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
    }
}

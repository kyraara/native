<?php

namespace Tests\Feature;

use App\Jobs\SendClientWhatsApp;
use App\Jobs\SendNewOrderWhatsApp;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\ServiceFormSchema;
use App\Support\ServiceFormTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_order_queues_client_and_admin_whatsapp(): void
    {
        Bus::fake();
        $this->enableWhatsApp();
        $service = $this->service();
        $token = $this->get('/order')->viewData('submissionToken');

        $this->post('/order', [
            'service_id' => $service->id, 'submission_token' => $token, 'title' => 'Jurnal', 'description' => 'Brief.',
            'deadline' => now('Asia/Jakarta')->addWeek()->toDateString(), 'client_name' => 'Alya', 'client_email' => 'a@example.com',
            'client_phone' => '081234567890', 'details' => $this->details(),
        ])->assertSessionHasNoErrors();

        $order = Order::sole();
        Bus::assertDispatched(SendNewOrderWhatsApp::class);
        Bus::assertDispatched(SendClientWhatsApp::class, fn ($job) => $job->orderId === $order->id && $job->kind === 'created');
    }

    public function test_client_toggles_and_missing_token_stop_messages(): void
    {
        Bus::fake();
        $this->enableWhatsApp();
        Setting::set('notify_client_created', '0');
        Setting::set('notify_admin_new_order', '0');
        $this->post('/order', $this->publicPayload())->assertSessionHasNoErrors();
        Bus::assertNotDispatched(SendClientWhatsApp::class);
        Bus::assertNotDispatched(SendNewOrderWhatsApp::class);

        Setting::set('notify_client_created', '1');
        config(['services.order_notifications.whatsapp.token' => '']);
        $this->post('/order', $this->publicPayload())->assertSessionHasNoErrors();
        Bus::assertNotDispatched(SendClientWhatsApp::class);
    }

    public function test_agreeing_price_and_starting_work_sends_one_combined_update(): void
    {
        Bus::fake();
        $this->enableWhatsApp();
        $order = $this->order();
        $this->actingAs($this->admin());

        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'processing', 'price_final' => 2600000])
            ->assertOk()->assertJsonPath('client_notified', true);
        Bus::assertDispatchedTimes(SendClientWhatsApp::class, 1);
        Bus::assertDispatched(SendClientWhatsApp::class, fn ($job) => $job->kind === 'updated' && $job->events === ['price' => true, 'status' => 'processing']);

        // Saving notes only, or opting out, sends nothing.
        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'processing', 'admin_notes' => 'Catatan'])->assertJsonPath('client_notified', false);
        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'review', 'notify_client' => false])->assertJsonPath('client_notified', false);
        Bus::assertDispatchedTimes(SendClientWhatsApp::class, 1);
    }

    public function test_job_sends_readable_message_to_client_and_logs_history(): void
    {
        $this->enableWhatsApp();
        Http::fake(['api.fonnte.test/*' => Http::response(['status' => true])]);
        $order = $this->order();

        (new SendClientWhatsApp($order->id, 'created'))->handle(app(\App\Services\FonnteClient::class), app(\App\Services\ClientMessages::class));

        Http::assertSent(function (Request $request) use ($order) {
            return $request['target'] === '6281234567890'
                && str_contains($request['message'], $order->tracking_token)
                && str_contains($request['message'], 'Peringkat SINTA: SINTA 2')
                && str_contains($request['message'], 'Estimasi mulai dari:* Rp 4.000.000')
                && str_contains($request['message'], route('order.track', $order->tracking_token));
        });
        $this->assertSame('notification', $order->activities()->sole()->action);
    }

    public function test_a_retried_job_never_sends_the_same_message_twice(): void
    {
        $this->enableWhatsApp();
        Http::fake(['api.fonnte.test/*' => Http::response(['status' => true])]);
        $order = $this->order();
        // Simulate the original bug: bookkeeping after sending blows up.
        \App\Models\OrderActivity::creating(fn () => throw new \RuntimeException('history unavailable'));

        $job = new SendClientWhatsApp($order->id, 'created');
        $handle = fn () => $job->handle(app(\App\Services\FonnteClient::class), app(\App\Services\ClientMessages::class));

        $handle(); // must not throw, otherwise the queue would retry and resend
        $handle(); // a retry of the same job is a no-op

        Http::assertSentCount(1);

        // A different message (new dispatch) is still delivered.
        (new SendClientWhatsApp($order->id, 'updated', ['status' => 'processing']))->handle(app(\App\Services\FonnteClient::class), app(\App\Services\ClientMessages::class));
        Http::assertSentCount(2);
    }

    public function test_failed_send_is_retried_and_admin_message_uses_whatsapp_formatting(): void
    {
        $this->enableWhatsApp();
        Http::fakeSequence('api.fonnte.test/*')
            ->push(['status' => false, 'reason' => 'device offline'])
            ->push(['status' => true]);
        $order = $this->order();
        $job = new SendNewOrderWhatsApp($order->id);

        try {
            $job->handle(app(\App\Services\FonnteClient::class));
            $this->fail('Rejected sends must throw so the queue retries.');
        } catch (\RuntimeException) {
        }
        $job->handle(app(\App\Services\FonnteClient::class));
        $job->handle(app(\App\Services\FonnteClient::class));

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request['target'] === '628111111111'
            && str_contains($request['message'], '*ORDER BARU MASUK*')
            && str_contains($request['message'], 'Peringkat SINTA: *SINTA 2*')
            && str_contains($request['message'], '*Estimasi:* Rp 4.000.000')
            && str_contains($request['message'], 'https://wa.me/6281234567890')
            && str_contains($request['message'], 'Buka order:'));
    }

    public function test_admin_can_send_a_test_email(): void
    {
        config(['services.order_notifications.email.recipient' => 'owner@example.com', 'mail.default' => 'array']);
        \Illuminate\Support\Facades\Mail::fake();

        $this->actingAs($this->admin())->from(route('admin.settings.notifications'))
            ->post(route('admin.settings.notifications.testEmail'))
            ->assertSessionHas('success');
    }

    public function test_admin_can_configure_notifications_and_override_admin_number(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('admin.settings.notifications'))->assertOk()->assertSee('Notifikasi ke klien');

        $this->put(route('admin.settings.notifications.update'), [
            'notify_admin_new_order' => '1', 'notify_client_created' => '1', 'notify_admin_target' => '0811-2222-3333',
        ])->assertSessionHasNoErrors();

        $this->assertSame('0', Setting::get('notify_client_status'));
        $this->assertSame('6281122223333', \App\Support\NotificationSettings::adminTarget());

        $this->put(route('admin.settings.notifications.update'), ['notify_admin_target' => 'abc'])->assertSessionHasErrors('notify_admin_target');
    }

    public function test_tracking_shows_estimate_until_final_price_and_catalog_lists_prices(): void
    {
        $order = $this->order();
        $this->withSession(['tracking_access' => [$order->tracking_token => now()->addMinutes(30)->timestamp]]);

        $this->get(route('order.track', $order))->assertOk()->assertSee('ESTIMASI AWAL')->assertSee('Mulai Rp 4.000.000')->assertSee('SINTA 2');
        $this->getJson(route('order.trackStatus', $order))->assertJsonPath('price_final', null)->assertJsonPath('price_estimate_label', 'Rp 4.000.000');

        $order->update(['price_final' => 3500000]);
        $this->get(route('order.track', $order))->assertSee('HARGA FINAL')->assertSee('Rp 3.500.000');

        $this->get('/')->assertOk()->assertSee('Lihat daftar harga')->assertSee('SINTA 3');
    }

    private function enableWhatsApp(): void
    {
        config([
            'services.order_notifications.whatsapp.enabled' => true,
            'services.order_notifications.whatsapp.endpoint' => 'https://api.fonnte.test/send',
            'services.order_notifications.whatsapp.token' => 'test-token',
            'services.order_notifications.whatsapp.target' => '628111111111',
        ]);
    }

    private function details(): array
    {
        return ['indeksasi' => 'sinta', 'peringkat_sinta' => 's2', 'kondisi_naskah' => 'draft', 'bidang_ilmu' => 'Manajemen', 'bahasa' => 'indonesia'];
    }

    private function publicPayload(): array
    {
        return [
            'service_id' => $this->service()->id, 'submission_token' => $this->get('/order')->viewData('submissionToken'),
            'title' => 'Jurnal', 'description' => 'Brief.', 'deadline' => now('Asia/Jakarta')->addWeek()->toDateString(),
            'client_name' => 'Alya', 'client_email' => 'a@example.com', 'client_phone' => '081234567890', 'details' => $this->details(),
        ];
    }

    private function service(): Service
    {
        return Service::firstOrCreate(['slug' => 'publish-jurnal'], [
            'name' => 'Publish Jurnal', 'description' => 'Jurnal.', 'icon_class' => 'fas fa-book', 'base_price' => 1000000,
            'is_active' => true, 'sort_order' => 1, 'form_fields' => ServiceFormTemplates::get('jurnal'),
        ]);
    }

    private function order(): Order
    {
        $service = $this->service();
        $snapshot = app(ServiceFormSchema::class)->snapshot($service->form_fields, $this->details(), $service->base_price);

        return Order::create([
            'tracking_token' => Str::upper(Str::random(16)), 'service_id' => $service->id, 'client_name' => 'Alya',
            'client_email' => 'a@example.com', 'client_phone' => '6281234567890', 'title' => 'Jurnal', 'description' => 'Brief.',
            'deadline' => now()->addWeek()->toDateString(), 'status' => 'pending', 'source' => 'web',
            'service_details' => $snapshot, 'price_estimate' => $snapshot['estimate']['total'],
        ]);
    }

    private function admin(): User
    {
        config(['admin.emails' => ['admin@example.com']]);

        return User::factory()->create(['email' => 'admin@example.com']);
    }
}

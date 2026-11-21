<?php

namespace Tests\Feature;

use App\Jobs\SendNewOrderEmail;
use App\Jobs\SendNewOrderWhatsApp;
use App\Mail\NewOrderAdminMail;
use App\Models\Order;
use App\Models\Service;
use App\Services\FonnteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_dispatches_email_and_whatsapp_jobs_when_configured(): void
    {
        Bus::fake();
        $this->enableNotifications();
        $service = $this->createService();
        $submissionToken = $this->get('/order')->viewData('submissionToken');

        $response = $this->post('/order', $this->validPayload($service, $submissionToken));

        $order = Order::firstOrFail();
        $response->assertRedirect(route('order.track', $order));
        Bus::assertDispatched(
            SendNewOrderEmail::class,
            fn (SendNewOrderEmail $job): bool => $job->orderId === $order->id,
        );
        Bus::assertDispatched(
            SendNewOrderWhatsApp::class,
            fn (SendNewOrderWhatsApp $job): bool => $job->orderId === $order->id,
        );
    }

    public function test_notification_jobs_are_not_dispatched_when_channels_are_disabled(): void
    {
        Bus::fake();
        config([
            'services.order_notifications.email.enabled' => false,
            'services.order_notifications.whatsapp.enabled' => false,
        ]);
        $service = $this->createService();
        $submissionToken = $this->get('/order')->viewData('submissionToken');

        $this->post('/order', $this->validPayload($service, $submissionToken))
            ->assertRedirect();

        Bus::assertNotDispatched(SendNewOrderEmail::class);
        Bus::assertNotDispatched(SendNewOrderWhatsApp::class);
    }

    public function test_email_job_sends_order_details_to_configured_admin(): void
    {
        Mail::fake();
        config(['services.order_notifications.email.recipient' => 'admin@example.com']);
        $order = $this->createOrder();

        $job = new SendNewOrderEmail($order->id);
        $job->handle();

        Mail::assertSent(
            NewOrderAdminMail::class,
            fn (NewOrderAdminMail $mail): bool => $mail->hasTo('admin@example.com')
                && $mail->order->is($order),
        );
        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300, 900], $job->backoff());
    }

    public function test_whatsapp_job_sends_order_details_through_fonnte(): void
    {
        config([
            'services.order_notifications.whatsapp.endpoint' => 'https://api.fonnte.test/send',
            'services.order_notifications.whatsapp.token' => 'secret-fonnte-token',
            'services.order_notifications.whatsapp.target' => '628111111111',
        ]);
        Http::fake([
            'api.fonnte.test/*' => Http::response(['status' => true], 200),
        ]);
        $order = $this->createOrder();

        $job = new SendNewOrderWhatsApp($order->id);
        $job->handle(app(FonnteClient::class));

        Http::assertSent(function (Request $request) use ($order): bool {
            return $request->url() === 'https://api.fonnte.test/send'
                && $request->hasHeader('Authorization', 'secret-fonnte-token')
                && $request['target'] === '628111111111'
                && str_contains($request['message'], $order->tracking_token)
                && str_contains($request['message'], 'Buka order:');
        });
        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300, 900], $job->backoff());
    }

    public function test_fonnte_rejection_throws_so_queue_can_retry_the_job(): void
    {
        config([
            'services.order_notifications.whatsapp.endpoint' => 'https://api.fonnte.test/send',
            'services.order_notifications.whatsapp.token' => 'secret-fonnte-token',
            'services.order_notifications.whatsapp.target' => '628111111111',
        ]);
        Http::fake([
            'api.fonnte.test/*' => Http::response([
                'status' => false,
                'reason' => 'Device disconnected.',
            ], 200),
        ]);
        $order = $this->createOrder();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Device disconnected.');

        (new SendNewOrderWhatsApp($order->id))->handle(app(FonnteClient::class));
    }

    private function enableNotifications(): void
    {
        config([
            'services.order_notifications.email.enabled' => true,
            'services.order_notifications.email.recipient' => 'admin@example.com',
            'services.order_notifications.whatsapp.enabled' => true,
            'services.order_notifications.whatsapp.token' => 'secret-fonnte-token',
            'services.order_notifications.whatsapp.target' => '628111111111',
        ]);
    }

    private function createOrder(): Order
    {
        $service = $this->createService();

        return Order::create([
            'tracking_token' => 'NOTIFYTOKEN12345',
            'service_id' => $service->id,
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'client_phone' => '628123456789',
            'title' => 'Contoh order baru',
            'description' => 'Deskripsi order untuk pengujian notifikasi.',
            'deadline' => now()->addWeek(),
            'budget' => '100rb-200rb',
            'status' => 'pending',
        ]);
    }

    private function createService(): Service
    {
        return Service::create([
            'name' => 'Tugas Kuliah',
            'slug' => 'tugas-kuliah',
            'description' => 'Bantuan tugas',
            'icon_class' => 'fas fa-book',
            'base_price' => 50000,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function validPayload(Service $service, string $submissionToken): array
    {
        return [
            'submission_token' => $submissionToken,
            'website' => '',
            'service_id' => $service->id,
            'title' => 'Contoh order baru',
            'description' => 'Deskripsi order untuk pengujian notifikasi.',
            'deadline' => now()->addWeek()->format('Y-m-d'),
            'budget' => '100rb-200rb',
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'client_phone' => '0812-3456-789',
        ];
    }
}

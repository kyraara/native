<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_created_once_and_attachment_is_private(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $service = $this->createService();
        $submissionToken = $this->get('/order')->viewData('submissionToken');

        $payload = $this->validPayload($service, $submissionToken, [
            'attachment' => UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf'),
        ]);

        $response = $this->post('/order', $payload);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('order.track', $order->tracking_token));
        $this->assertSame('628123456789', $order->client_phone);
        $this->assertSame(16, strlen($order->tracking_token));
        Storage::disk('local')->assertExists($order->attachment_path);
        Storage::disk('public')->assertMissing($order->attachment_path);

        $this->post('/order', $payload)->assertSessionHasErrors('submission_token');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_inactive_service_and_archive_upload_are_rejected(): void
    {
        $service = $this->createService(['is_active' => false]);
        $submissionToken = $this->get('/order')->viewData('submissionToken');

        $this->post('/order', $this->validPayload($service, $submissionToken, [
            'attachment' => UploadedFile::fake()->create('archive.zip', 100, 'application/zip'),
        ]))->assertSessionHasErrors(['service_id', 'attachment']);

        $this->assertDatabaseEmpty('orders');
    }

    public function test_order_submission_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/order', [])->assertSessionHasErrors();
        }

        $this->post('/order', [])->assertTooManyRequests();
    }

    private function createService(array $attributes = []): Service
    {
        return Service::create(array_merge([
            'name' => 'Tugas Kuliah',
            'slug' => 'tugas-kuliah',
            'description' => 'Bantuan tugas',
            'icon_class' => 'fas fa-book',
            'base_price' => 50000,
            'is_active' => true,
            'sort_order' => 1,
        ], $attributes));
    }

    private function validPayload(Service $service, string $submissionToken, array $overrides = []): array
    {
        return array_merge([
            'submission_token' => $submissionToken,
            'website' => '',
            'service_id' => $service->id,
            'title' => 'Contoh tugas',
            'description' => 'Deskripsi tugas yang cukup lengkap.',
            'deadline' => now()->addWeek()->format('Y-m-d'),
            'budget' => '100rb-200rb',
            'client_name' => 'Test Client',
            'client_email' => 'CLIENT@EXAMPLE.COM',
            'client_phone' => '0812-3456-789',
        ], $overrides);
    }
}

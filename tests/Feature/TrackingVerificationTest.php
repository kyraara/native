<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackingVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_links_and_status_api_require_verification_without_disclosing_an_order(): void
    {
        $order = $this->order();
        $this->get(route('order.track', $order))->assertRedirect(route('order.trackSearch', ['token' => $order->tracking_token]))->assertDontSee($order->title);
        $this->getJson(route('order.trackStatus', $order))->assertForbidden()->assertJsonMissing(['title' => $order->title]);
        $this->getJson('/track/UNKNOWN/status')->assertForbidden();
    }

    public function test_code_requires_exactly_four_digits_and_correct_contact(): void
    {
        $order = $this->order(['client_phone' => '628123450007']);
        foreach ([null, '007', '00007', '12ab', ['0007']] as $digits) {
            $this->post('/track', ['token' => $order->tracking_token, 'phone_last4' => $digits])->assertSessionHasErrors('phone_last4');
        }
        $wrong = $this->post('/track', ['token' => $order->tracking_token, 'phone_last4' => '9999']);
        $wrong->assertRedirect(route('order.trackSearch'))->assertSessionHasErrors('phone_last4');
        $message = session('errors')->first('phone_last4');
        $this->post('/track', ['token' => 'UNKNOWN', 'phone_last4' => '9999'])->assertSessionHasErrors('phone_last4');
        $this->assertSame($message, session('errors')->first('phone_last4'));
        $this->post('/track', ['token' => strtolower($order->tracking_token), 'phone_last4' => '0007'])->assertRedirect(route('order.track', $order));
        $this->get(route('order.track', $order))->assertOk()->assertSee($order->title)->assertDontSee($order->client_phone);
        $response = $this->getJson(route('order.trackStatus', $order))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_access_is_scoped_to_order_and_expires(): void
    {
        $order = $this->order(); $other = $this->order();
        $this->post('/track', ['token' => $order->tracking_token, 'phone_last4' => '7890'])->assertRedirect();
        $this->getJson(route('order.trackStatus', $order))->assertOk();
        $this->getJson(route('order.trackStatus', $other))->assertForbidden();
        $this->travel(31)->minutes();
        $this->getJson(route('order.trackStatus', $order))->assertForbidden();
        $this->get(route('order.track', $order))->assertRedirect(route('order.trackSearch', ['token' => $order->tracking_token]));
    }

    public function test_repeated_wrong_contact_checks_are_limited(): void
    {
        $order = $this->order();
        for ($i = 0; $i < 5; $i++) $this->post('/track', ['token' => $order->tracking_token, 'phone_last4' => '0000'])->assertSessionHasErrors('phone_last4');
        $this->post('/track', ['token' => $order->tracking_token, 'phone_last4' => '7890'])->assertSessionHasErrors('phone_last4');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('phone_last4'));
        $this->getJson(route('order.trackStatus', $order))->assertForbidden();
        $this->travel(61)->seconds();
        $this->post('/track', ['token' => $order->tracking_token, 'phone_last4' => '7890'])->assertRedirect(route('order.track', $order));
    }

    public function test_json_order_returns_tracking_and_retry_does_not_duplicate_order(): void
    {
        Bus::fake();
        $payload = $this->payload();
        $payload['submission_token'] = $this->get('/order')->viewData('submissionToken');
        $response = $this->postJson('/order', $payload)->assertCreated();
        $order = Order::firstOrFail();
        $response->assertJsonPath('redirect_url', route('order.track', $order));
        $this->get($response->json('redirect_url'))->assertOk()->assertSee('Order kamu berhasil dikirim.');
        $this->postJson('/order', $payload)->assertOk()->assertJsonPath('redirect_url', route('order.track', $order));
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_rejected_json_order_can_be_corrected_with_same_form(): void
    {
        Bus::fake();
        $payload = $this->payload();
        $payload['submission_token'] = $this->get('/order')->viewData('submissionToken');
        $this->postJson('/order', array_replace($payload, ['deadline' => now('Asia/Jakarta')->toDateString()]))->assertUnprocessable()->assertJsonValidationErrors('deadline');
        $this->assertDatabaseEmpty('orders');
        $this->postJson('/order', $payload)->assertCreated();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_chat_and_tracking_activity_do_not_consume_order_submission_limit(): void
    {
        Bus::fake();
        $payload = $this->payload();
        $payload['submission_token'] = $this->get('/order')->viewData('submissionToken');
        for ($i = 0; $i < 6; $i++) {
            $this->getJson('/chat/history')->assertOk();
            $this->getJson('/track/UNVERIFIED/status')->assertForbidden();
        }
        $this->postJson('/order', $payload)->assertCreated();
        $this->assertDatabaseCount('orders', 1);
    }

    private function payload(): array
    {
        $service = Service::create(['name' => 'Presentasi', 'slug' => Str::uuid(), 'description' => 'Sesuai brief', 'icon_class' => 'fas fa-display', 'base_price' => 50000, 'is_active' => true]);
        return ['service_id' => $service->id, 'title' => 'Brief pribadi pelanggan', 'description' => 'Detail order', 'deadline' => now('Asia/Jakarta')->addWeek()->toDateString(), 'client_name' => 'Pelanggan', 'client_email' => 'qa@example.test', 'client_phone' => '6281234567890'];
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_replace($this->payload(), ['tracking_token' => Str::upper(Str::random(16)), 'status' => 'pending'], $attributes));
    }
}

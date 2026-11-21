<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicOrderExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_and_form_only_offer_active_majors_with_active_services(): void
    {
        $service = $this->service();
        $inactive = $this->service(['is_active' => false, 'name' => 'Layanan Arsip']);
        $major = $this->major('Informatika');
        $hidden = $this->major('Jurusan Arsip', ['is_active' => false]);
        $empty = $this->major('Jurusan Tanpa Layanan');
        $major->services()->attach($service);
        $hidden->services()->attach($service);
        $empty->services()->attach($inactive);

        foreach (['/', '/order'] as $url) {
            $this->get($url)->assertOk()
                ->assertViewHas('majors', fn ($items) => $items->modelKeys() === [$major->id])
                ->assertViewHas('services', fn ($items) => $items->modelKeys() === [$service->id] && $items->first()->majors->modelKeys() === [$major->id])
                ->assertDontSee('Layanan Arsip')->assertDontSee('Jurusan Arsip');
        }
    }

    public function test_jurusan_filter_is_optional_but_cannot_submit_an_unrelated_service(): void
    {
        Bus::fake();
        $service = $this->service();
        $major = $this->major('Informatika');
        $token = $this->get('/order')->viewData('submissionToken');
        $payload = $this->payload($service, $token) + ['major_id' => $major->id];

        $this->post('/order', $payload)->assertSessionHasErrors('service_id');
        $this->assertDatabaseEmpty('orders');
        $major->services()->attach($service);
        $this->post('/order', $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('orders', 1);

        $token = $this->get('/order')->viewData('submissionToken');
        $this->post('/order', $this->payload($service, $token))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_public_deadline_validation_uses_jakarta_calendar_day(): void
    {
        Bus::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-07 18:00:00', 'UTC'));
        $service = $this->service();
        $token = $this->get('/order')->viewData('submissionToken');
        $payload = $this->payload($service, $token);
        $this->post('/order', array_replace($payload, ['deadline' => '2026-11-08']))->assertSessionHasErrors('deadline');
        $this->post('/order', array_replace($payload, ['deadline' => '2026-11-09']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_tracking_poll_shows_admin_changes_and_only_customer_fields(): void
    {
        $order = $this->order();
        $this->withSession(['tracking_access' => [$order->tracking_token => now()->addMinutes(30)->timestamp]]);
        $endpoint = route('order.trackStatus', $order);
        $initial = $this->getJson($endpoint)->assertOk()->assertJsonPath('price_final', null)->assertJsonPath('completed_label', null);
        $this->assertStringContainsString('no-store', $initial->headers->get('Cache-Control'));
        foreach (['client_email', 'client_phone', 'attachment_path', 'description', 'id'] as $privateField) {
            $this->assertArrayNotHasKey($privateField, $initial->json());
        }

        $admin = User::factory()->create(['email' => 'public-test-admin@example.com']);
        config(['admin.emails' => [$admin->email]]);
        $this->actingAs($admin)->patchJson(route('admin.orders.updateStatus', $order), [
            'status' => 'review', 'deadline' => '2026-12-20', 'price_final' => '275000.50', 'admin_notes' => 'Deadline diperbarui sesuai konsultasi.',
        ])->assertOk();
        auth()->logout();
        $this->getJson($endpoint)->assertOk()->assertJsonPath('status', 'review')
            ->assertJsonPath('deadline', '2026-12-20')->assertJsonPath('deadline_label', '20 Desember 2026')
            ->assertJsonPath('price_final', '275000.50')->assertJsonPath('price_label', 'Rp 275.000,50')
            ->assertJsonPath('admin_notes', 'Deadline diperbarui sesuai konsultasi.');
        $this->get(route('order.track', $order))->assertOk()->assertSee('noindex, nofollow')
            ->assertDontSee($order->client_email)->assertDontSee($order->client_phone)->assertDontSee($order->attachment_path);
        $this->getJson('/track/NOTFOUND/status')->assertForbidden();
    }

    public function test_deadline_today_is_not_overdue_and_zero_price_is_confirmed(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-07 18:00:00', 'UTC'));
        $order = $this->order(['deadline' => '2026-11-08', 'price_final' => 0]);
        $this->withSession(['tracking_access' => [$order->tracking_token => now()->addMinutes(30)->timestamp]]);
        $endpoint = route('order.trackStatus', $order);
        $this->getJson($endpoint)->assertJsonPath('overdue', false)->assertJsonPath('deadline_hint', 'Tenggat hari ini')->assertJsonPath('price_label', 'Rp 0');
        $order->update(['deadline' => '2026-11-07']);
        $this->getJson($endpoint)->assertJsonPath('overdue', true);
        $order->update(['status' => 'done']);
        $this->getJson($endpoint)->assertJsonPath('overdue', false)->assertJsonPath('completed_label', '08 November 2026, 01:00 WIB');
        $order->update(['status' => 'processing']);
        $this->getJson($endpoint)->assertJsonPath('completed_at', null)->assertJsonPath('completed_label', null);
    }

    public function test_tracking_search_accepts_lowercase_and_legacy_tokens_but_rejects_malformed_input(): void
    {
        $order = $this->order(['tracking_token' => 'NC1234']);
        $this->post('/track', ['token' => '  nc1234  ', 'phone_last4' => '7890'])->assertRedirect(route('order.track', $order));
        $this->post('/track', ['token' => '<script>alert(1)</script>'])->assertSessionHasErrors('token');
        $this->post('/track', ['token' => ['bad']])->assertSessionHasErrors('token');
        $this->get('/track')->assertOk();
    }

    public function test_home_preserves_manual_statistics_and_configured_contact_details(): void
    {
        $this->service(['name' => 'Layanan Aktif']);
        Setting::set('total_orders', '612');
        Setting::set('rating', '4.95');
        Setting::set('happy_clients', '450+');
        Setting::set('location', 'Bandung, Jawa Barat');
        Setting::set('wa_number', '0812-3456-7890');

        $this->get('/')->assertOk()->assertSee('<strong>612</strong>', false)->assertDontSee('612+')
            ->assertSee('<strong>4.95', false)->assertSee('450+')->assertSee('Bandung, Jawa Barat')
            ->assertSee('https://wa.me/6281234567890', false)->assertSee('Layanan Aktif');
    }

    public function test_empty_catalog_and_array_preselection_render_without_errors(): void
    {
        $this->get('/')->assertOk()->assertSee('Katalog sedang disiapkan.');
        $this->get('/order?service[]=1&major[]=2')->assertOk()->assertSee('Belum ada layanan yang tersedia.');
    }

    private function service(array $attributes = []): Service
    {
        return Service::create(array_replace(['name' => 'Presentasi PPT', 'slug' => Str::uuid(), 'description' => 'Presentasi sesuai brief.', 'icon_class' => 'fas fa-book', 'base_price' => 50000, 'is_active' => true], $attributes));
    }

    private function major(string $name, array $attributes = []): Major
    {
        return Major::create(array_replace(['name' => $name, 'slug' => Str::uuid(), 'is_active' => true], $attributes));
    }

    private function payload(Service $service, string $token): array
    {
        return ['service_id' => $service->id, 'submission_token' => $token, 'title' => 'Presentasi proposal', 'description' => 'Brief proposal lengkap.', 'deadline' => now('Asia/Jakarta')->addWeek()->toDateString(), 'client_name' => 'Alya', 'client_email' => 'alya@example.com', 'client_phone' => '081234567890'];
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_replace($this->payload($this->service(), ''), ['tracking_token' => Str::upper(Str::random(16)), 'status' => 'pending', 'attachment_path' => 'attachments/private-brief.pdf'], $attributes));
    }
}

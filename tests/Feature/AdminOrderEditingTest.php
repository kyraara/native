<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\ServiceFormSchema;
use App\Support\ServiceFormTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_order_cannot_start_work_without_a_final_price(): void
    {
        $order = $this->order();
        $this->actingAs($this->admin());

        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'processing'])
            ->assertUnprocessable()->assertJsonValidationErrors('price_final');
        $this->assertSame('pending', $order->fresh()->status);

        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'processing', 'price_final' => 2600000])->assertOk();
        $order->refresh();
        $this->assertSame('processing', $order->status);
        $this->assertNotNull($order->price_finalized_at);
        $this->assertEqualsCanonicalizing(['status', 'price_final'], $order->activities->pluck('action')->all());

        // Cancelling never needs a price.
        $this->patchJson(route('admin.orders.updateStatus', $this->order()), ['status' => 'cancelled'])->assertOk();
    }

    public function test_admin_can_add_an_order_from_whatsapp_without_email(): void
    {
        Bus::fake();
        $service = $this->service();

        $this->actingAs($this->admin())->post(route('admin.orders.store'), $this->payload($service, [
            'details' => $this->details('s2'), 'client_email' => '', 'client_phone' => '0812-3456-7890', 'source' => 'whatsapp',
        ]))->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('order_created');

        $order = Order::sole();
        $this->assertNull($order->client_email);
        $this->assertSame('6281234567890', $order->client_phone);
        $this->assertSame('whatsapp', $order->source);
        $this->assertSame('4000000.00', $order->price_estimate);
        $this->assertSame('created', $order->activities()->sole()->action);
        Bus::assertNothingDispatched(); // admin-added orders don't notify the admin

        $this->get(route('admin.orders.show', $order))->assertOk()
            ->assertSee('SINTA 2')->assertSee('Kirim via WhatsApp')->assertSee($order->tracking_token);
    }

    public function test_admin_order_started_immediately_needs_a_final_price(): void
    {
        $service = $this->service();

        $this->actingAs($this->admin())->post(route('admin.orders.store'), $this->payload($service, ['details' => $this->details('s3'), 'status' => 'processing']))
            ->assertSessionHasErrors('price_final');
        $this->assertDatabaseEmpty('orders');

        $this->post(route('admin.orders.store'), $this->payload($service, ['details' => $this->details('s3'), 'status' => 'processing', 'price_final' => 2500000]))
            ->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing(['created', 'price_final'], Order::sole()->activities->pluck('action')->all());
    }

    public function test_switching_sinta_2_to_sinta_3_recomputes_estimate_and_logs_the_change(): void
    {
        $service = $this->service();
        $order = $this->order(['price_final' => 4000000]);
        $this->actingAs($this->admin());

        $this->get(route('admin.orders.edit', $order))->assertOk()->assertSee('Simpan perubahan');

        $this->put(route('admin.orders.update', $order), $this->payload($service, ['details' => $this->details('s3')]))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('2500000.00', $order->price_estimate);
        $this->assertSame('4000000.00', $order->price_final, 'The agreed price never changes silently.');
        $this->assertSame('SINTA 3', collect($order->detail_answers)->firstWhere('key', 'peringkat_sinta')['display']);

        $activity = $order->activities()->sole();
        $this->assertSame('edited', $activity->action);
        $this->assertContains(['field' => 'Peringkat SINTA', 'from' => 'SINTA 2', 'to' => 'SINTA 3'], $activity->changes);
        $this->assertContains(['field' => 'Estimasi', 'from' => 'Rp 4.000.000', 'to' => 'Rp 2.500.000'], $activity->changes);

        // Optionally reopen the price for a new agreement.
        $this->put(route('admin.orders.update', $order), $this->payload($service, ['details' => $this->details('s2'), 'reset_price_final' => 1]))->assertSessionHasNoErrors();
        $this->assertNull($order->fresh()->price_final);
        $this->assertNull($order->fresh()->price_finalized_at);
    }

    public function test_saving_an_unchanged_order_does_not_add_history(): void
    {
        $service = $this->service();
        $order = $this->order();

        $this->actingAs($this->admin())->put(route('admin.orders.update', $order), $this->payload($service, ['details' => $this->details('s2')]))
            ->assertSessionHas('success', 'Tidak ada perubahan pada order.');
        $this->assertSame(0, $order->activities()->count());
    }

    public function test_order_list_is_paginated_with_a_selectable_page_size(): void
    {
        foreach (range(1, 27) as $i) $this->order(['title' => "Order {$i}"]);
        $this->actingAs($this->admin());

        $this->get(route('admin.orders.index'))->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 10 && $orders->total() === 27)
            ->assertSee('1–10 dari 27 order');
        $this->get(route('admin.orders.index', ['per_page' => 25, 'page' => 2]))
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 2);
        $this->get(route('admin.orders.index', ['per_page' => 1000]))
            ->assertViewHas('perPage', 10);
    }

    public function test_whatsapp_template_links_to_this_apps_tracking_page(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin())->get(route('admin.orders.show', $order))->assertOk()
            ->assertSee(urlencode(route('order.track', $order->tracking_token)), false)
            ->assertDontSee('native.kyra.my.id');
    }

    public function test_order_pages_require_admin(): void
    {
        $order = $this->order();
        config(['admin.emails' => ['admin@example.com']]);
        $this->actingAs(User::factory()->create(['email' => 'client@example.com']));

        $this->get(route('admin.orders.create'))->assertForbidden();
        $this->get(route('admin.orders.edit', $order))->assertForbidden();
        $this->put(route('admin.orders.update', $order), [])->assertForbidden();
    }

    private function details(string $rank): array
    {
        return ['indeksasi' => 'sinta', 'peringkat_sinta' => $rank, 'kondisi_naskah' => 'draft', 'bidang_ilmu' => 'Manajemen', 'bahasa' => 'indonesia'];
    }

    private function payload(Service $service, array $overrides = []): array
    {
        return array_replace([
            'service_id' => $service->id, 'title' => 'Publikasi jurnal', 'description' => 'Brief.',
            'deadline' => now()->addWeek()->toDateString(), 'client_name' => 'Alya', 'client_email' => 'alya@example.com',
            'client_phone' => '6281234567890', 'source' => 'web', 'status' => 'pending',
        ], $overrides);
    }

    private function service(): Service
    {
        return Service::firstOrCreate(['slug' => 'publish-jurnal'], [
            'name' => 'Publish Jurnal', 'description' => 'Jurnal.', 'icon_class' => 'fas fa-book', 'base_price' => 1000000,
            'is_active' => true, 'sort_order' => 1, 'form_fields' => ServiceFormTemplates::get('jurnal'),
        ]);
    }

    private function order(array $attributes = []): Order
    {
        $service = $this->service();
        $snapshot = app(ServiceFormSchema::class)->snapshot($service->form_fields, $this->details('s2'), $service->base_price);

        return Order::create(array_replace([
            'tracking_token' => Str::upper(Str::random(16)), 'service_id' => $service->id, 'client_name' => 'Alya',
            'client_email' => 'alya@example.com', 'client_phone' => '6281234567890', 'title' => 'Publikasi jurnal', 'description' => 'Brief.',
            'deadline' => now()->addWeek()->toDateString(), 'status' => 'pending', 'source' => 'web',
            'service_details' => $snapshot, 'price_estimate' => $snapshot['estimate']['total'],
        ], $attributes));
    }

    private function admin(): User
    {
        config(['admin.emails' => ['admin@example.com']]);

        return User::factory()->create(['email' => 'admin@example.com']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Support\ServiceFormTemplates;
use Database\Seeders\ServiceFormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceFormOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sinta_order_requires_a_rank_and_stores_a_server_side_estimate(): void
    {
        Bus::fake();
        $service = $this->service(ServiceFormTemplates::get('jurnal'));
        $details = ['indeksasi' => 'sinta', 'kondisi_naskah' => 'draft', 'bidang_ilmu' => 'Ekonomi', 'bahasa' => 'indonesia'];

        $this->post('/order', $this->payload($service, ['details' => $details]))
            ->assertSessionHasErrors('details.peringkat_sinta');
        $this->assertDatabaseEmpty('orders');

        $this->post('/order', $this->payload($service, [
            'details' => $details + ['peringkat_sinta' => 's2', 'bantuan_tambahan' => ['turnitin']],
            'price_estimate' => 1, // must be ignored
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $order = Order::sole();
        $this->assertSame('4250000.00', $order->price_estimate);
        $this->assertNull($order->price_final);
        $this->assertSame('SINTA 2', collect($order->detail_answers)->firstWhere('key', 'peringkat_sinta')['display']);
        $this->assertSame('Parafrase & cek Turnitin', collect($order->detail_answers)->firstWhere('key', 'bantuan_tambahan')['display']);
    }

    public function test_options_outside_the_list_and_out_of_range_quantities_are_rejected(): void
    {
        $service = $this->service(ServiceFormTemplates::get('ppt'));

        $this->post('/order', $this->payload($service, ['details' => ['keperluan' => 'gratis', 'jumlah_slide' => 2]]))
            ->assertSessionHasErrors(['details.keperluan', 'details.jumlah_slide']);
        $this->assertDatabaseEmpty('orders');
    }

    public function test_non_sinta_drops_the_hidden_rank_answer(): void
    {
        Bus::fake();
        $service = $this->service(ServiceFormTemplates::get('jurnal'));

        $this->post('/order', $this->payload($service, ['details' => [
            'indeksasi' => 'non_sinta', 'peringkat_sinta' => 's1', 'kondisi_naskah' => 'siap', 'bidang_ilmu' => 'Hukum', 'bahasa' => 'inggris',
        ]]))->assertSessionHasNoErrors();

        $order = Order::sole();
        $this->assertNull(collect($order->detail_answers)->firstWhere('key', 'peringkat_sinta'));
        $this->assertSame('1000000.00', $order->price_estimate);
    }

    public function test_service_without_fields_still_orders_with_its_base_price(): void
    {
        Bus::fake();
        $service = $this->service(null, ['base_price' => 40000]);

        $this->post('/order', $this->payload($service))->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('40000.00', Order::sole()->price_estimate);
        $this->assertSame([], Order::sole()->detail_answers);
    }

    public function test_old_orders_keep_their_estimate_when_admin_changes_prices(): void
    {
        Bus::fake();
        $fields = ServiceFormTemplates::get('ppt');
        $service = $this->service($fields);
        $this->post('/order', $this->payload($service, ['details' => ['keperluan' => 'sidang', 'jumlah_slide' => 10]]))->assertSessionHasNoErrors();

        $fields[0]['options'][2]['price'] = 999999;
        $service->update(['form_fields' => $fields]);

        $this->assertSame('150000.00', Order::sole()->price_estimate);
        $this->assertSame(100000, Order::sole()->estimate_lines[0]['amount']);
    }

    public function test_starting_price_is_recomputed_on_save_and_seeder_keeps_admin_fields(): void
    {
        $service = $this->service(null, ['slug' => 'presentasi-ppt', 'base_price' => 60000]);
        $this->assertSame('60000.00', $service->starting_price);

        $this->seed(ServiceFormSeeder::class);
        $this->assertSame('75000.00', $service->fresh()->starting_price);

        $custom = [['key' => 'catatan', 'label' => 'Catatan', 'type' => 'text']];
        $service->update(['form_fields' => $custom]);
        $this->seed(ServiceFormSeeder::class);
        $this->assertSame($custom, $service->fresh()->form_fields);
    }

    public function test_setting_the_final_price_records_when_it_was_agreed(): void
    {
        $service = $this->service();
        $order = Order::create([
            'tracking_token' => Str::upper(Str::random(16)), 'service_id' => $service->id, 'client_name' => 'Alya',
            'client_email' => 'alya@example.com', 'client_phone' => '6281234567890', 'title' => 'T', 'description' => 'D',
            'deadline' => now()->addWeek(), 'status' => 'pending', 'price_estimate' => 100000,
        ]);
        $this->assertNull($order->price_finalized_at);

        $order->update(['price_final' => 120000]);
        $this->assertNotNull($order->fresh()->price_finalized_at);

        $order->update(['price_final' => null]);
        $this->assertNull($order->fresh()->price_finalized_at);
    }

    private function service(?array $fields = null, array $attributes = []): Service
    {
        return Service::create(array_replace([
            'name' => 'Layanan Uji', 'slug' => (string) Str::uuid(), 'description' => 'Uji.', 'icon_class' => 'fas fa-book',
            'base_price' => 50000, 'is_active' => true, 'form_fields' => $fields,
        ], $attributes));
    }

    private function payload(Service $service, array $extra = []): array
    {
        $token = $this->get('/order')->viewData('submissionToken');

        return array_replace([
            'service_id' => $service->id, 'submission_token' => $token, 'title' => 'Order uji', 'description' => 'Brief lengkap.',
            'deadline' => now('Asia/Jakarta')->addWeek()->toDateString(), 'client_name' => 'Alya',
            'client_email' => 'alya@example.com', 'client_phone' => '081234567890',
        ], $extra);
    }
}

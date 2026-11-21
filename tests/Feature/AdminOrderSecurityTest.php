<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOrderSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_attachment_is_available_only_through_admin_download(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $order = $this->order(['attachment_path' => 'attachments/brief.pdf']);
        Storage::disk('local')->put($order->attachment_path, 'private content');

        $this->get(route('admin.orders.attachment', $order))->assertRedirect('/login');
        $this->actingAs($admin)
            ->get(route('admin.orders.attachment', $order))
            ->assertOk();
    }

    public function test_csv_export_neutralizes_spreadsheet_formulas(): void
    {
        $admin = $this->admin();
        $this->order([
            'client_name' => '=HYPERLINK("https://example.test")',
            'title' => '+SUM(1,1)',
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.orders.export'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString("'+SUM", $content);
    }

    private function admin(): User
    {
        config(['admin.emails' => ['admin@example.com']]);

        return User::factory()->create(['email' => 'admin@example.com']);
    }

    private function order(array $attributes = []): Order
    {
        $service = Service::create([
            'name' => 'Tugas Kuliah',
            'slug' => 'tugas-kuliah',
            'description' => 'Bantuan tugas',
            'icon_class' => 'fas fa-book',
            'base_price' => 50000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Order::create(array_merge([
            'tracking_token' => 'SECURETOKEN12345',
            'service_id' => $service->id,
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'client_phone' => '628123456789',
            'title' => 'Contoh tugas',
            'description' => 'Deskripsi tugas',
            'deadline' => now()->addWeek(),
            'budget' => '100rb',
            'status' => 'pending',
        ], $attributes));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_major_and_map_services(): void
    {
        $service = $this->service();

        $this->actingAs($this->admin())->post(route('admin.majors.store'), [
            'name' => 'Teknik Informatika',
            'description' => 'Jurusan teknologi dan komputasi.',
            'icon_class' => 'fas fa-laptop-code',
            'sort_order' => 1,
            'is_active' => 1,
            'service_ids' => [$service->id],
        ])->assertRedirect();

        $major = Major::where('slug', 'teknik-informatika')->firstOrFail();
        $this->assertTrue($major->is_active);
        $this->assertTrue($major->services->contains($service));
    }

    public function test_admin_can_create_service_and_map_majors(): void
    {
        $major = Major::create([
            'name' => 'Sistem Informasi',
            'slug' => 'sistem-informasi',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin())->post(route('admin.services.store'), [
            'name' => 'Analisis Sistem',
            'description' => 'Bantuan analisis dan perancangan sistem.',
            'icon_class' => 'fas fa-diagram-project',
            'base_price' => 150000,
            'sort_order' => 2,
            'is_active' => 1,
            'major_ids' => [$major->id],
        ])->assertRedirect();

        $service = Service::where('slug', 'analisis-sistem')->firstOrFail();
        $this->assertTrue($service->majors->contains($major));
    }

    public function test_service_with_order_cannot_be_deleted(): void
    {
        $service = $this->service();
        Order::create([
            'tracking_token' => 'CATALOGTOKEN1234',
            'service_id' => $service->id,
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
            'client_phone' => '628123456789',
            'title' => 'Order berkaitan dengan layanan',
            'description' => 'Riwayat order harus tetap menyimpan layanan.',
            'deadline' => now()->addWeek(),
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.services.destroy', $service))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_non_admin_cannot_manage_catalog(): void
    {
        config(['admin.emails' => ['admin@example.com']]);
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)->get(route('admin.catalog.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.majors.store'), [])->assertForbidden();
    }

    private function admin(): User
    {
        config(['admin.emails' => ['admin@example.com']]);

        return User::factory()->create(['email' => 'admin@example.com']);
    }

    private function service(): Service
    {
        return Service::create([
            'name' => 'Pemrograman Web',
            'slug' => 'pemrograman-web',
            'description' => 'Bantuan website dan aplikasi web.',
            'icon_class' => 'fas fa-code',
            'base_price' => 300000,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_authenticated_non_admin_cannot_access_admin(): void
    {
        config(['admin.emails' => ['admin@example.com']]);
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_allowlisted_admin_can_access_admin(): void
    {
        config(['admin.emails' => ['admin@example.com']]);
        $admin = User::factory()->create(['email' => 'ADMIN@example.com']);

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }
}

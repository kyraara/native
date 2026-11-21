<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_revise_deadline_price_and_notes_after_consultation(): void
    {
        $order = $this->order(['deadline' => '2026-11-10', 'price_final' => 100000]);

        $this->actingAs($this->admin())->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.updateStatus', $order), [
                'status' => 'processing', 'deadline' => '2026-11-20',
                'price_final' => '275000.50', 'admin_notes' => 'Deadline sesuai konsultasi.',
            ])->assertRedirect(route('admin.orders.show', $order))->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('2026-11-20', $order->deadline->format('Y-m-d'));
        $this->assertSame('275000.50', $order->price_final);
        $this->assertSame('Deadline sesuai konsultasi.', $order->admin_notes);
        $this->withSession(['tracking_access' => [$order->tracking_token => now()->addMinutes(30)->timestamp]])
            ->get(route('order.track', $order))->assertOk()->assertSee('20 November 2026')->assertSee('275.000,50');
    }

    public function test_invalid_deadline_or_price_does_not_change_order(): void
    {
        $order = $this->order(['price_final' => 100000]);
        $this->actingAs($this->admin());

        foreach ([['deadline' => '2026-02-30'], ['deadline' => ''], ['price_final' => -1], ['price_final' => 100000000], ['price_final' => '1.234']] as $invalid) {
            $this->patchJson(route('admin.orders.updateStatus', $order), array_merge(['status' => 'done'], $invalid))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid));
        }

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('100000.00', $order->fresh()->price_final);
        $this->assertNull($order->fresh()->completed_at);
    }

    public function test_inline_status_preserves_consultation_fields_and_records_completion_once(): void
    {
        $order = $this->order(['price_final' => 125000, 'admin_notes' => 'Tetap disimpan.']);
        $deadline = $order->deadline->toDateString();
        $this->travelTo(CarbonImmutable::parse('2026-10-31 17:30:00', 'UTC'));
        $this->actingAs($this->admin())->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'done'])->assertOk();
        $completed = $order->fresh()->completed_at->toDateTimeString();

        $this->travelTo(CarbonImmutable::parse('2026-12-10 10:00:00', 'UTC'));
        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'done', 'price_final' => 175000])->assertOk();
        $order->refresh();
        $this->assertSame($completed, $order->completed_at->toDateTimeString());
        $this->assertSame($deadline, $order->deadline->toDateString());
        $this->assertSame('Tetap disimpan.', $order->admin_notes);
        $this->get(route('admin.reports.revenue', ['month' => '2026-11']))->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['orders'] === 1 && (float) $summary['revenue'] === 175000.0);
    }

    public function test_explicitly_clearing_price_differs_from_zero(): void
    {
        $order = $this->order(['status' => 'done', 'price_final' => 200000, 'admin_notes' => 'Catatan lama']);
        $this->actingAs($this->admin())->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'done', 'price_final' => '', 'admin_notes' => ''])->assertOk();
        $this->assertNull($order->fresh()->price_final);
        $this->assertNull($order->fresh()->admin_notes);
        $this->get(route('admin.reports.revenue'))->assertViewHas('summary', fn ($summary) => $summary['unpriced'] === 1);

        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'done', 'price_final' => '0'])->assertOk();
        $this->assertSame('0.00', $order->fresh()->price_final);
        $this->get(route('admin.reports.revenue'))->assertViewHas('summary', fn ($summary) => $summary['unpriced'] === 0);
    }

    public function test_report_excludes_unfinished_and_cancelled_orders_and_totals_all_pages(): void
    {
        $this->actingAs($this->admin());
        foreach (range(1, 16) as $i) $this->order(['status' => 'done', 'price_final' => 1000]);
        $this->order(['status' => 'done', 'price_final' => null]);
        $this->order(['status' => 'done', 'price_final' => 0]);
        foreach (['pending', 'processing', 'review', 'cancelled'] as $status) $this->order(['status' => $status, 'price_final' => 900000]);

        $this->get(route('admin.reports.revenue', ['page' => 2]))->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['orders'] === 18 && (float) $summary['revenue'] === 16000.0 && $summary['unpriced'] === 1)
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 3)
            ->assertSee('Rp 16.000');
    }

    public function test_monthly_report_uses_jakarta_completion_boundaries_and_year(): void
    {
        $this->actingAs($this->admin());
        foreach ([['2026-10-31 16:59:59', 100], ['2026-10-31 17:00:00', 200], ['2026-11-30 16:59:59', 300], ['2026-11-30 17:00:00', 400], ['2025-11-15 00:00:00', 500]] as [$date, $price]) {
            $this->travelTo(CarbonImmutable::parse($date, 'UTC'));
            $this->order(['status' => 'done', 'price_final' => $price]);
        }
        $this->get(route('admin.reports.revenue', ['month' => '2026-11']))->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['orders'] === 2 && (float) $summary['revenue'] === 500.0);
        $this->getJson(route('admin.reports.revenue', ['month' => '2026-13']))->assertUnprocessable();
    }

    public function test_historical_orders_with_unknown_completion_date_remain_in_all_time_report(): void
    {
        $order = $this->order(['status' => 'done', 'price_final' => 75000]);
        DB::table('orders')->where('id', $order->id)->update(['completed_at' => null]);
        $this->actingAs($this->admin())->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'done', 'price_final' => 95000])->assertOk();
        $this->assertNull($order->fresh()->completed_at);
        $this->get(route('admin.reports.revenue'))->assertOk()->assertViewHas('undated', 1)
            ->assertViewHas('summary', fn ($summary) => (float) $summary['revenue'] === 95000.0);
        $this->get(route('admin.reports.revenue', ['month' => now('Asia/Jakarta')->format('Y-m')]))
            ->assertViewHas('summary', fn ($summary) => $summary['orders'] === 0);
    }

    public function test_reopening_an_order_removes_it_from_revenue_until_completed_again(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 12:00:00', 'UTC'));
        $order = $this->order(['status' => 'done', 'price_final' => 250000]);
        $this->actingAs($this->admin())->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'review'])->assertOk();
        $this->assertNull($order->fresh()->completed_at);
        $this->get(route('admin.reports.revenue'))->assertViewHas('summary', fn ($summary) => $summary['orders'] === 0);
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00:00', 'UTC'));
        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'done'])->assertOk();
        $this->get(route('admin.reports.revenue', ['month' => '2026-11']))->assertViewHas('summary', fn ($summary) => $summary['orders'] === 1);
    }

    public function test_notification_list_includes_only_pending_orders_and_updates_after_status_change(): void
    {
        foreach (range(1, 7) as $i) $this->order(['title' => 'Order pending '.$i]);
        $this->order(['status' => 'done', 'title' => 'Order selesai']);
        $this->actingAs($this->admin())->getJson(route('admin.notifications'))->assertOk()
            ->assertJsonPath('count', 7)->assertJsonCount(5, 'items')->assertJsonPath('items.0.title', 'Order pending 7');
        $order = Order::where('title', 'Order pending 7')->firstOrFail();
        // Starting work requires an agreed final price.
        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'processing', 'price_final' => 150000])->assertOk();
        $this->getJson(route('admin.notifications'))->assertJsonPath('count', 6)->assertJsonPath('items.0.title', 'Order pending 6');
        foreach (['admin.dashboard', 'admin.orders.index', 'admin.catalog.index', 'admin.settings.index', 'admin.reports.revenue'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Notifikasi order')->assertSee('admin-notification-panel');
        }
    }

    public function test_revenue_export_matches_month_and_neutralizes_spreadsheet_formulas(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-07 10:00:00', 'UTC'));
        $order = $this->order(['status' => 'done', 'price_final' => '12345.67', 'client_name' => '=SUM(1,1)']);
        $excluded = $this->order(['status' => 'cancelled', 'price_final' => 999999]);
        $this->travelTo(CarbonImmutable::parse('2026-10-07 10:00:00', 'UTC'));
        $earlier = $this->order(['status' => 'done', 'price_final' => 100]);
        $content = $this->actingAs($this->admin())->get(route('admin.reports.revenue.export', ['month' => '2026-11']))
            ->assertOk()->assertDownload('penghasilan-2026-11.csv')->streamedContent();

        $this->assertStringContainsString($order->tracking_token, $content);
        $this->assertStringContainsString("'=SUM(1,1)", $content);
        $this->assertStringContainsString('12345.67', $content);
        $this->assertStringNotContainsString($excluded->tracking_token, $content);
        $this->assertStringNotContainsString($earlier->tracking_token, $content);
    }

    public function test_reports_notifications_and_order_changes_require_admin_access(): void
    {
        config(['admin.emails' => ['admin@example.com']]);
        $order = $this->order();
        $this->getJson(route('admin.notifications'))->assertUnauthorized();
        $this->get(route('admin.reports.revenue'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['email' => 'client@example.com']));
        foreach (['admin.notifications', 'admin.reports.revenue', 'admin.reports.revenue.export'] as $route) $this->get(route($route))->assertForbidden();
        $this->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'done', 'deadline' => '2026-12-01'])->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    private function admin(): User
    {
        config(['admin.emails' => ['admin@example.com']]);

        return User::factory()->create(['email' => 'admin@example.com']);
    }

    private function order(array $attributes = []): Order
    {
        $service = Service::firstOrCreate(['slug' => 'admin-test'], [
            'name' => 'Pemrograman Web', 'description' => 'Layanan uji', 'icon_class' => 'fas fa-code',
            'base_price' => 100000, 'is_active' => true, 'sort_order' => 1,
        ]);

        return Order::create(array_merge([
            'tracking_token' => Str::upper(Str::random(18)), 'service_id' => $service->id,
            'client_name' => 'Klien Uji', 'client_email' => 'client@example.com', 'client_phone' => '628123456789',
            'title' => 'Order untuk pengujian', 'description' => 'Pengujian admin',
            'deadline' => now()->addWeek()->toDateString(), 'status' => 'pending',
        ], $attributes));
    }
}

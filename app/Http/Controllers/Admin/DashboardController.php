<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\Service;
use App\Services\OrderRevenue;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(OrderRevenue $revenue): View
    {
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $counts = Order::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        $stats = [
            'majors' => Major::where('is_active', true)->count(),
            'services' => Service::where('is_active', true)->count(),
        ];

        // "Perlu tindakan": what an admin should look at first.
        $needsPrice = Order::with('service')->where('status', 'pending')->whereNull('price_final')
            ->orderBy('created_at')->limit(5)->get();
        $overdue = Order::with('service')->whereNotIn('status', ['done', 'cancelled'])
            ->whereDate('deadline', '<', $today->toDateString())->orderBy('deadline')->limit(5)->get();
        $inReview = Order::with('service')->where('status', 'review')->orderBy('deadline')->limit(5)->get();
        $actions = $this->actions($needsPrice, $overdue, $inReview);
        $actionTotals = [
            'needs_price' => Order::where('status', 'pending')->whereNull('price_final')->count(),
            'overdue' => Order::whereNotIn('status', ['done', 'cancelled'])->whereDate('deadline', '<', $today->toDateString())->count(),
            'review' => (int) ($counts['review'] ?? 0),
        ];

        $upcoming = Order::with('service')->whereNotIn('status', ['done', 'cancelled'])
            ->orderBy('deadline')->orderBy('id')->limit(6)->get();

        // Orders created per day, last 7 days (Jakarta calendar days).
        $days = collect(range(6, 0))->map(fn (int $i) => $today->subDays($i));
        $ordersRaw = Order::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $today->subDays(7))
            ->groupBy('date')->pluck('total', 'date');
        $chartDays = $days->map(fn (CarbonImmutable $day) => [
            'label' => $day->locale('id')->translatedFormat('D'),
            'date' => $day->locale('id')->translatedFormat('d M'),
            'total' => (int) $ordersRaw->get($day->toDateString(), 0),
            'today' => $day->isSameDay($today),
        ]);

        $activities = OrderActivity::with(['order:id,tracking_token,title', 'user:id,name'])
            ->whereHas('order')->latest()->latest('id')->limit(6)->get();

        $earnings = $revenue->summary();
        $monthEarnings = $revenue->summary(CarbonImmutable::now('Asia/Jakarta'));

        return view('admin.dashboard', compact(
            'stats', 'counts', 'actions', 'actionTotals', 'upcoming', 'chartDays', 'activities', 'earnings', 'monthEarnings',
        ));
    }

    /** Merge the three action lists, most urgent first, without listing the same order twice. */
    private function actions(Collection $needsPrice, Collection $overdue, Collection $inReview): Collection
    {
        $items = collect();

        foreach ($overdue as $order) {
            $items->put($order->id, ['order' => $order, 'tag' => 'Lewat deadline', 'tone' => 'late']);
        }
        foreach ($needsPrice as $order) {
            $items->put($order->id, $items->get($order->id) ?? ['order' => $order, 'tag' => 'Perlu harga final', 'tone' => 'price']);
        }
        foreach ($inReview as $order) {
            $items->put($order->id, $items->get($order->id) ?? ['order' => $order, 'tag' => 'Menunggu review', 'tone' => 'review']);
        }

        return $items->values()->take(7);
    }
}

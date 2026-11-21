<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class OrderRevenue
{
    public function completed(?CarbonImmutable $month = null): Builder
    {
        $query = Order::where('status', 'done');

        if ($month) {
            $start = $month->startOfMonth();
            $query->where('completed_at', '>=', $start->setTimezone(config('app.timezone')))
                ->where('completed_at', '<', $start->addMonth()->setTimezone(config('app.timezone')));
        }

        return $query;
    }

    public function summary(?CarbonImmutable $month = null): array
    {
        $summary = $this->completed($month)->selectRaw(
            'COUNT(*) as total_orders, COALESCE(SUM(price_final), 0) as revenue, '
            .'COALESCE(SUM(CASE WHEN price_final IS NULL THEN 1 ELSE 0 END), 0) as unpriced'
        )->first();

        return [
            'orders' => (int) $summary->total_orders,
            'revenue' => $summary->revenue,
            'unpriced' => (int) $summary->unpriced,
        ];
    }
}

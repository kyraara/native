<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderRevenue;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueController extends Controller
{
    public function index(Request $request, OrderRevenue $revenue): View
    {
        $month = $this->month($request);
        $summary = $revenue->summary($month);
        $allTime = $month ? $revenue->summary() : $summary;
        $undated = $revenue->completed()->whereNull('completed_at')->count();
        $orders = $revenue->completed($month)->with('service')
            ->orderByDesc('completed_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.reports.revenue', compact('month', 'summary', 'allTime', 'undated', 'orders'));
    }

    public function export(Request $request, OrderRevenue $revenue): StreamedResponse
    {
        $month = $this->month($request);
        $query = $revenue->completed($month)->with('service')->orderByDesc('completed_at')->orderByDesc('id');

        return response()->streamDownload(function () use ($query): void {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Token', 'Klien', 'Layanan', 'Judul', 'Tanggal Selesai (WIB)', 'Harga Final (Rp)'], ',', '"', '');

            foreach ($query->lazy(200) as $order) {
                $row = [
                    $order->tracking_token,
                    $order->client_name,
                    $order->service?->name ?? '',
                    $order->title,
                    $order->completed_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? 'Belum tercatat',
                    $order->price_final ?? '',
                ];
                $row = array_map(function ($value): string {
                    $value = (string) $value;

                    return preg_match('/^\s*[=+\-@]|^[\t\r]/u', $value) ? "'".$value : $value;
                }, $row);
                fputcsv($file, $row, ',', '"', '');
            }
            fclose($file);
        }, 'penghasilan-'.($month?->format('Y-m') ?? 'semua-periode').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function month(Request $request): ?CarbonImmutable
    {
        $validated = $request->validate(['month' => 'nullable|date_format:Y-m']);

        return filled($validated['month'] ?? null)
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['month'], 'Asia/Jakarta')
            : null;
    }
}

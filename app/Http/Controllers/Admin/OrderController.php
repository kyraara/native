<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with('service')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('tracking_token', 'like', "%{$request->search}%")
                  ->orWhere('client_name', 'like', "%{$request->search}%")
                  ->orWhere('title', 'like', "%{$request->search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();
        $statuses = Order::STATUSES;

        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order): View
    {
        $order->load('service');
        $statuses = Order::STATUSES;
        return view('admin.orders.show', compact('order', 'statuses'));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $request->validate([
            'status'      => 'required|in:' . implode(',', array_keys(Order::STATUSES)),
            'admin_notes' => 'nullable|string|max:2000',
            'price_final' => 'nullable|numeric|min:0',
        ]);

        $order->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes ?? $order->admin_notes,
            'price_final' => $request->price_final ?? $order->price_final,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message'      => 'Status berhasil diperbarui.',
                'status'       => $order->status,
                'status_label' => Order::STATUSES[$order->status]['label'] ?? $order->status,
            ]);
        }

        return back()->with('success', 'Status order berhasil diperbarui.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        if ($order->attachment_path) {
            Storage::disk('public')->delete($order->attachment_path);
        }

        $order->delete();

        return redirect()->route('admin.orders.index')
            ->with('success', "Order #{$order->tracking_token} berhasil dihapus.");
    }

    public function export(Request $request): Response
    {
        $query = Order::with('service')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('tracking_token', 'like', "%{$request->search}%")
                  ->orWhere('client_name', 'like', "%{$request->search}%")
                  ->orWhere('title', 'like', "%{$request->search}%");
            });
        }

        $orders = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders-' . now()->format('Y-m-d') . '.csv"',
        ];

        $rows = [];
        $rows[] = ['Token', 'Klien', 'Email', 'WhatsApp', 'Layanan', 'Judul', 'Deadline', 'Budget', 'Harga Final', 'Status', 'Catatan Admin', 'Tanggal Order'];

        foreach ($orders as $order) {
            $rows[] = [
                $order->tracking_token,
                $order->client_name,
                $order->client_email,
                $order->client_phone,
                $order->service?->name ?? '',
                $order->title,
                $order->deadline->format('Y-m-d'),
                $order->budget ?? '',
                $order->price_final ?? '',
                Order::STATUSES[$order->status]['label'] ?? $order->status,
                $order->admin_notes ?? '',
                $order->created_at->format('Y-m-d H:i'),
            ];
        }

        $csv = implode("\n", array_map(fn($row) => implode(',', array_map(
            fn($cell) => '"' . str_replace('"', '""', $cell) . '"',
            $row
        )), $rows));

        return response("\xEF\xBB\xBF" . $csv, 200, $headers);
    }
}

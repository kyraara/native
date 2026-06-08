<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(): View
    {
        $services = Service::active()->get();
        return view('pages.order.create', compact('services'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id'   => 'required|exists:services,id',
            'title'        => 'required|string|max:255',
            'description'  => 'required|string|max:5000',
            'deadline'     => 'required|date|after:today',
            'budget'       => 'nullable|string|max:100',
            'attachment'   => 'nullable|file|max:10240|mimes:pdf,doc,docx,zip,rar,jpg,png',
            'client_name'  => 'required|string|max:100',
            'client_email' => 'required|email|max:100',
            'client_phone' => 'required|string|max:20',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        // Generate unique token, retry jika collision
        do {
            $token = strtoupper(Str::random(3) . rand(100, 999) . Str::random(3));
        } while (Order::where('tracking_token', $token)->exists());

        $order = Order::create([
            'tracking_token'  => $token,
            'service_id'      => $validated['service_id'],
            'title'           => $validated['title'],
            'description'     => $validated['description'],
            'deadline'        => $validated['deadline'],
            'budget'          => $validated['budget'] ?? null,
            'attachment_path' => $attachmentPath,
            'client_name'     => $validated['client_name'],
            'client_email'    => $validated['client_email'],
            'client_phone'    => $validated['client_phone'],
            'status'          => 'pending',
        ]);

        return redirect()->route('order.track', $order->tracking_token)
            ->with('success', 'Order berhasil dikirim! Simpan kode tracking kamu.');
    }

    public function trackSearch(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $request->validate(['token' => 'required|string|min:3|max:20']);
            $token = strtoupper(trim($request->token));
            $order = Order::where('tracking_token', $token)->first();

            if (! $order) {
                return back()->withInput()->with('error', "Kode tracking \"{$token}\" tidak ditemukan. Pastikan kode yang kamu masukkan benar.");
            }

            return redirect()->route('order.track', $token);
        }

        return view('pages.order.track-search');
    }

    public function track(string $token): View
    {
        $order = Order::with('service')->where('tracking_token', $token)->firstOrFail();
        return view('pages.order.tracking', compact('order'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Service;
use App\Models\Major;
use App\Services\OrderNotificationDispatcher;
use App\Services\PublicOrderTracking;
use App\Services\ServiceFormSchema;
use App\Services\ClientNotifier;
use App\Services\OrderTrackingAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(): View
    {
        $services = Service::active()->with(['majors' => fn ($query) => $query->active()])->get();
        $majors = Major::active()->whereHas('services', fn ($query) => $query->where('is_active', true))->get();
        $submissionToken = (string) Str::uuid();

        session()->put("order_submission_tokens.{$submissionToken}", true);

        return view('pages.order.create', compact('services', 'majors', 'submissionToken'));
    }

    public function store(
        StoreOrderRequest $request,
        OrderNotificationDispatcher $notifications,
        OrderTrackingAccess $access,
        ServiceFormSchema $schema,
        ClientNotifier $clientNotifier,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();
        $tokenKey = "order_submission_tokens.{$validated['submission_token']}";

        $previous = $request->session()->get("order_submission_results.{$validated['submission_token']}");
        if ($request->expectsJson() && is_string($previous)) {
            $access->grant($request, $previous);
            return response()->json(['redirect_url' => route('order.track', $previous)]);
        }

        if (! $request->session()->get($tokenKey, false)) {
            throw ValidationException::withMessages([
                'submission_token' => 'Form order sudah digunakan atau kedaluwarsa. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'local');
        }

        $token = Order::generateTrackingToken();

        // The estimate is always recomputed from the service definition — never taken from the browser.
        $service = $request->selectedService();
        $snapshot = $schema->snapshot($service->form_fields ?? [], $request->details(), $service->base_price);

        $order = Order::create([
            'tracking_token' => $token,
            'service_id' => $validated['service_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'service_details' => $snapshot,
            'deadline' => $validated['deadline'],
            'budget' => $validated['budget'] ?? null,
            'price_estimate' => $snapshot['estimate']['total'],
            'attachment_path' => $attachmentPath,
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'],
            'client_phone' => $validated['client_phone'],
            'status' => 'pending',
            'source' => 'web',
        ]);
        $order->logActivity('created', 'Order masuk dari website.');

        $request->session()->forget($tokenKey);
        $request->session()->put("order_submission_results.{$validated['submission_token']}", $token);
        $access->grant($request, $token);
        $notifications->dispatch($order);
        $clientNotifier->orderReceived($order);

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Order berhasil dikirim! Simpan kode tracking kamu.');
            return response()->json(['redirect_url' => route('order.track', $token)], 201);
        }

        return redirect()->route('order.track', $order->tracking_token)
            ->with('success', 'Order berhasil dikirim! Simpan kode tracking kamu.');
    }

    public function trackSearch(Request $request, OrderTrackingAccess $access): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            if (is_string($request->input('token'))) {
                $request->merge(['token' => strtoupper(trim($request->input('token')))]);
            }
            $request->validate([
                'token' => ['required', 'string', 'regex:/^[A-Z0-9]{3,20}$/'],
                'phone_last4' => ['required', 'string', 'regex:/^[0-9]{4}$/'],
            ], [
                'token.required' => 'Masukkan kode tracking order kamu.',
                'token.regex' => 'Kode tracking hanya berisi huruf dan angka, maksimal 20 karakter.',
                'token.string' => 'Masukkan kode tracking yang valid.',
                'phone_last4.*' => 'Masukkan tepat 4 digit terakhir nomor WhatsApp pemesan.',
            ]);
            $token = $request->token;
            $limiterKey = 'tracking-verification:'.hash('sha256', $request->ip().'|'.$token);
            if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
                return redirect()->route('order.trackSearch')->withInput($request->only('token'))->withErrors(['phone_last4' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($limiterKey).' detik.']);
            }
            RateLimiter::hit($limiterKey, 60);
            $order = Order::where('tracking_token', $token)->first();

            if (! $order || ! hash_equals(substr(preg_replace('/\D/', '', $order->client_phone), -4), $request->phone_last4)) {
                return redirect()->route('order.trackSearch')->withInput($request->only('token'))->withErrors(['phone_last4' => 'Kode tracking atau 4 digit WhatsApp belum cocok. Periksa keduanya lalu coba lagi.']);
            }

            RateLimiter::clear($limiterKey);
            $access->grant($request, $token);
            return redirect()->route('order.track', $token);
        }

        return view('pages.order.track-search');
    }

    public function track(Request $request, string $token, PublicOrderTracking $tracking, OrderTrackingAccess $access): Response|RedirectResponse
    {
        if (! $access->allows($request, $token)) {
            return redirect()->route('order.trackSearch', ['token' => $token]);
        }
        $order = Order::with('service')->where('tracking_token', $token)->firstOrFail();

        $snapshot = $tracking->snapshot($order);

        return response()->view('pages.order.tracking', compact('order', 'snapshot'))->header('Cache-Control', 'no-store, private');
    }

    public function trackStatus(Request $request, string $token, PublicOrderTracking $tracking, OrderTrackingAccess $access): JsonResponse
    {
        if (! $access->allows($request, $token)) {
            return response()->json(['message' => 'Verifikasi nomor WhatsApp diperlukan.'], 403)->header('Cache-Control', 'no-store, private');
        }
        $order = Order::where('tracking_token', $token)->firstOrFail();

        return response()->json($tracking->snapshot($order))
            ->header('Cache-Control', 'no-store, private');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOrderRequest;
use App\Models\Order;
use App\Models\Service;
use App\Services\ClientNotifier;
use App\Services\ServiceFormSchema;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(private readonly ServiceFormSchema $schema, private readonly ClientNotifier $clientNotifier)
    {
    }

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

        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE_OPTIONS, true) ? (int) $request->query('per_page') : self::PER_PAGE_OPTIONS[0];
        $orders = $query->paginate($perPage)->withQueryString();
        $statuses = Order::STATUSES;
        $statusCounts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $perPageOptions = self::PER_PAGE_OPTIONS;

        return view('admin.orders.index', compact('orders', 'statuses', 'statusCounts', 'perPage', 'perPageOptions'));
    }

    public function show(Order $order): View
    {
        $order->load(['service', 'activities.user']);
        $statuses = Order::STATUSES;

        return view('admin.orders.show', compact('order', 'statuses'));
    }

    public function create(): View
    {
        $order = new Order([
            'status' => 'pending',
            'source' => 'whatsapp',
            'deadline' => now('Asia/Jakarta')->addWeek(),
        ]);

        return $this->form($order);
    }

    public function store(AdminOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $service = $request->selectedService();
        $snapshot = $this->schema->snapshot($service->form_fields ?? [], $request->details(), $service->base_price);

        $order = DB::transaction(function () use ($request, $validated, $service, $snapshot) {
            $order = Order::create([
                'tracking_token' => Order::generateTrackingToken(),
                'service_id' => $service->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'service_details' => $snapshot,
                'deadline' => $validated['deadline'],
                'budget' => $validated['budget'] ?? null,
                'price_estimate' => $snapshot['estimate']['total'],
                'price_final' => $validated['price_final'] ?? null,
                'attachment_path' => $request->hasFile('attachment') ? $request->file('attachment')->store('attachments', 'local') : null,
                'client_name' => $validated['client_name'],
                'client_email' => $validated['client_email'] ?? null,
                'client_phone' => $validated['client_phone'],
                'status' => $validated['status'],
                'source' => $validated['source'],
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            $order->logActivity('created', 'Order ditambahkan admin (sumber: '.$order->source_label.').');
            if ($order->price_final !== null) {
                $order->logActivity('price_final', 'Harga final ditetapkan '.Money::rupiah($order->price_final).'.');
            }

            return $order;
        });

        $notified = $request->boolean('notify_client') && $this->clientNotifier->orderReceived($order);

        // The detail page shows its own "order saved" banner with the WhatsApp button.
        return redirect()->route('admin.orders.show', $order)->with('order_created', true)->with('client_notified', $notified);
    }

    public function edit(Order $order): View
    {
        return $this->form($order->load('service'));
    }

    public function update(AdminOrderRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();
        $service = $request->selectedService();
        $snapshot = $this->schema->snapshot($service->form_fields ?? [], $request->details(), $service->base_price);
        $before = $order->replicate()->setRelation('service', $order->service);

        $changes = $this->describeChanges($before, $service, $snapshot, $validated, $request->hasFile('attachment'));
        $resetPrice = (bool) ($validated['reset_price_final'] ?? false) && $order->price_final !== null;

        DB::transaction(function () use ($request, $order, $validated, $service, $snapshot, $changes, $resetPrice) {
            $previousAttachment = $order->attachment_path;

            $order->fill([
                'service_id' => $service->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'service_details' => $snapshot,
                'deadline' => $validated['deadline'],
                'budget' => $validated['budget'] ?? null,
                'price_estimate' => $snapshot['estimate']['total'],
                'client_name' => $validated['client_name'],
                'client_email' => $validated['client_email'] ?? null,
                'client_phone' => $validated['client_phone'],
                'source' => $validated['source'],
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            if ($request->hasFile('attachment')) {
                $order->attachment_path = $request->file('attachment')->store('attachments', 'local');
            }

            if ($resetPrice) {
                $changes[] = ['field' => 'Harga final', 'from' => Money::rupiah($order->price_final), 'to' => 'Dikosongkan (perlu disepakati ulang)'];
                $order->price_final = null;
            }

            $order->save();

            if ($request->hasFile('attachment') && $previousAttachment) {
                Storage::disk('local')->delete($previousAttachment);
            }

            if ($changes !== []) {
                $order->logActivity('edited', 'Detail order diperbarui ('.count($changes).' perubahan).', $changes);
            }
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('success', $changes === [] ? 'Tidak ada perubahan pada order.' : 'Perubahan order berhasil disimpan.');
    }

    public function updateStatus(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status'      => 'required|in:' . implode(',', array_keys(Order::STATUSES)),
            'admin_notes' => 'nullable|string|max:2000',
            'price_final' => 'nullable|numeric|decimal:0,2|min:0|max:99999999.99',
            'deadline'    => 'sometimes|required|date_format:Y-m-d',
            'notify_client' => 'sometimes|boolean',
        ]);

        $priceAfter = array_key_exists('price_final', $validated) ? $validated['price_final'] : $order->price_final;
        if (Order::needsFinalPriceFor($order->status, $validated['status'], $priceAfter)) {
            throw ValidationException::withMessages([
                'price_final' => 'Isi harga final dulu sebelum order mulai dikerjakan. Harga final disepakati saat konsultasi dengan klien.',
            ]);
        }

        $before = ['status' => $order->status, 'price_final' => $order->price_final, 'deadline' => $order->deadline?->toDateString(), 'admin_notes' => $order->admin_notes];

        // Omitted fields in the inline status request stay unchanged. Explicitly
        // clearing the detail form can remove a quote or a client note.
        $order->update(collect($validated)->except('notify_client')->all());
        $this->logStatusChanges($order, $before);

        $priceAgreed = $order->price_final !== null && ($before['price_final'] === null || (float) $before['price_final'] !== (float) $order->price_final);
        $notified = $request->boolean('notify_client', true) && $this->clientNotifier->orderUpdated($order, $before['status'], $priceAgreed);

        if ($request->expectsJson()) {
            return response()->json([
                'message'      => 'Status berhasil diperbarui.',
                'status'       => $order->status,
                'status_label' => Order::STATUSES[$order->status]['label'] ?? $order->status,
                'client_notified' => $notified,
            ]);
        }

        return back()->with('success', 'Perubahan order berhasil disimpan.'.($notified ? ' Notifikasi WhatsApp ke klien sedang dikirim.' : ''));
    }

    private function form(Order $order): View
    {
        $services = Service::query()->orderByDesc('is_active')->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name.($service->is_active ? '' : ' (nonaktif)'),
                'base_price' => (float) $service->base_price,
                'price' => $service->display_price,
                'fields' => $service->formSchema(),
            ])->values();

        $answers = collect($order->detail_answers)->mapWithKeys(fn (array $answer) => [$answer['key'] => $answer['value']])->all();

        return view('admin.orders.form', [
            'order' => $order,
            'services' => $services,
            'answers' => $answers,
            'statuses' => Order::STATUSES,
            'sources' => Order::SOURCES,
        ]);
    }

    /** Human-readable list of what an edit changes, for the order history. */
    private function describeChanges(Order $before, Service $service, array $snapshot, array $validated, bool $newAttachment): array
    {
        $changes = [];
        $add = function (string $field, mixed $from, mixed $to) use (&$changes): void {
            $from = $from === null || $from === '' ? '—' : (string) $from;
            $to = $to === null || $to === '' ? '—' : (string) $to;
            if ($from !== $to) {
                $changes[] = ['field' => $field, 'from' => $from, 'to' => $to];
            }
        };

        $add('Layanan', $before->service?->name, $service->name);

        $oldAnswers = collect($before->detail_answers)->keyBy('label')->map(fn ($answer) => $answer['display']);
        $newAnswers = collect($snapshot['answers'])->keyBy('label')->map(fn ($answer) => $answer['display']);
        foreach ($oldAnswers->keys()->merge($newAnswers->keys())->unique() as $label) {
            $add($label, $oldAnswers[$label] ?? null, $newAnswers[$label] ?? null);
        }

        $add('Estimasi', $before->price_estimate !== null ? Money::rupiah($before->price_estimate) : null, Money::rupiah($snapshot['estimate']['total']));
        $add('Judul', $before->title, $validated['title']);
        if ($before->description !== $validated['description']) {
            $changes[] = ['field' => 'Detail & instruksi', 'from' => 'Teks lama', 'to' => 'Teks diperbarui'];
        }
        $add('Deadline', $before->deadline?->locale('id')->translatedFormat('d F Y'), \Illuminate\Support\Carbon::parse($validated['deadline'])->locale('id')->translatedFormat('d F Y'));
        $add('Budget klien', $before->budget, $validated['budget'] ?? null);
        $add('Nama klien', $before->client_name, $validated['client_name']);
        $add('Email', $before->client_email, $validated['client_email'] ?? null);
        $add('WhatsApp', $before->client_phone, $validated['client_phone']);
        $add('Sumber order', $before->source_label, Order::SOURCES[$validated['source']] ?? $validated['source']);
        if (($before->admin_notes ?? '') !== ($validated['admin_notes'] ?? '')) {
            $changes[] = ['field' => 'Pesan untuk klien', 'from' => $before->admin_notes ? 'Pesan lama' : '—', 'to' => ($validated['admin_notes'] ?? '') !== '' ? 'Pesan diperbarui' : 'Dikosongkan'];
        }
        if ($newAttachment) {
            $changes[] = ['field' => 'Lampiran', 'from' => $before->attachment_path ? 'Lampiran lama' : '—', 'to' => 'Lampiran baru'];
        }

        return $changes;
    }

    private function logStatusChanges(Order $order, array $before): void
    {
        if ($before['status'] !== $order->status) {
            $order->logActivity('status', 'Status diubah: '.(Order::STATUSES[$before['status']]['label'] ?? $before['status']).' → '.$order->status_label.'.');
        }

        $oldPrice = $before['price_final'] === null ? null : (float) $before['price_final'];
        $newPrice = $order->price_final === null ? null : (float) $order->price_final;
        if ($oldPrice !== $newPrice) {
            $order->logActivity('price_final', match (true) {
                $oldPrice === null => 'Harga final ditetapkan '.Money::rupiah($newPrice).'.',
                $newPrice === null => 'Harga final dikosongkan ('.Money::rupiah($oldPrice).' sebelumnya).',
                default => 'Harga final diubah '.Money::rupiah($oldPrice).' → '.Money::rupiah($newPrice).'.',
            });
        }

        if ($before['deadline'] !== $order->deadline?->toDateString()) {
            $order->logActivity('deadline', 'Deadline diubah menjadi '.$order->deadline->locale('id')->translatedFormat('d F Y').'.');
        }

        if (($before['admin_notes'] ?? '') !== ($order->admin_notes ?? '')) {
            $order->logActivity('notes', $order->admin_notes ? 'Pesan untuk klien diperbarui.' : 'Pesan untuk klien dikosongkan.');
        }
    }

    public function downloadAttachment(Order $order): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_if(!$order->attachment_path, 404);

        if (Storage::disk('local')->exists($order->attachment_path)) {
            return Storage::disk('local')->download($order->attachment_path);
        }

        // Temporary backwards compatibility for files uploaded before private storage was enabled.
        abort_if(!Storage::disk('public')->exists($order->attachment_path), 404);

        return Storage::disk('public')->download($order->attachment_path);
    }

    public function destroy(Order $order): RedirectResponse
    {
        if ($order->attachment_path) {
            Storage::disk('local')->delete($order->attachment_path);
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

        $sanitizeCsvCell = function ($cell): string {
            $value = (string) $cell;

            if (preg_match('/^[=+\-@\t\r]/u', $value)) {
                $value = "'".$value;
            }

            return $value;
        };

        $csv = implode("\n", array_map(fn($row) => implode(',', array_map(
            fn($cell) => '"' . str_replace('"', '""', $sanitizeCsvCell($cell)) . '"',
            $row
        )), $rows));

        return response("\xEF\xBB\xBF" . $csv, 200, $headers);
    }
}

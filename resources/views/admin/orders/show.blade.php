@extends('layouts.admin')

@section('title', "Order #{$order->tracking_token}")
@section('page-title', 'Detail Order')
@section('page-subtitle', "#{$order->tracking_token} · {$order->client_name}")

@section('content')

@php
    $statusColors = [
        'pending'    => 'bg-yellow-100 text-yellow-700 border-yellow-200',
        'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
        'review'     => 'bg-purple-100 text-purple-700 border-purple-200',
        'done'       => 'bg-green-100 text-green-700 border-green-200',
        'cancelled'  => 'bg-red-100 text-red-700 border-red-200',
    ];
    $waPhone = preg_replace('/[^0-9]/', '', $order->client_phone);
    if (str_starts_with($waPhone, '0')) $waPhone = '62' . substr($waPhone, 1);
    $statusLabel = \App\Models\Order::STATUSES[$order->status]['label'] ?? $order->status;
    $deadlineStr = $order->deadline->locale('id')->isoFormat('D MMMM YYYY');
    $trackUrl    = 'https://native.kyra.my.id/track/' . $order->tracking_token;

    $waLines = [
        "Halo *{$order->client_name}*! 👋",
        "",
        "Berikut update terbaru order kamu di *NativeCuy* ya 😊",
        "",
        "📋 *No. Order:* {$order->tracking_token}",
        "📚 *Layanan:* " . ($order->service?->name ?? '-'),
        "📅 *Deadline:* {$deadlineStr}",
        "📌 *Status:* {$statusLabel}",
    ];

    if ($order->admin_notes) {
        $waLines[] = "";
        $waLines[] = "💬 *Pesan dari Admin:*";
        $waLines[] = $order->admin_notes;
    }

    $waLines = array_merge($waLines, [
        "",
        "🔗 Pantau progress real-time di sini:",
        $trackUrl,
        "",
        "Kalau ada pertanyaan, balas pesan ini aja ya! 🦉",
        "— *NativeCuy*",
    ]);

    $waMsg = urlencode(implode("\n", $waLines));
    $waUrl = "https://wa.me/{$waPhone}?text={$waMsg}";
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="{
        deleteModal: false,
        copyDone: false,
        copyTrackingLink() {
            navigator.clipboard.writeText('{{ route('order.track', $order->tracking_token) }}').then(() => {
                this.copyDone = true;
                Alpine.store('adminToast').fire('Link tracking disalin!', 'success');
                setTimeout(() => this.copyDone = false, 2500);
            });
        }
     }">

    {{-- Left: Order detail --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Status banner --}}
        <div class="flex items-center justify-between bg-white rounded-2xl shadow-sm border px-6 py-4 {{ $statusColors[$order->status] ?? 'border-gray-100' }}">
            <div>
                <p class="text-xs font-medium opacity-70">Status Order Saat Ini</p>
                <p class="font-display font-bold text-lg">{{ \App\Models\Order::STATUSES[$order->status]['label'] ?? $order->status }}</p>
            </div>
            <div class="text-3xl opacity-20">
                <i class="{{ \App\Models\Order::STATUSES[$order->status]['icon'] ?? 'fas fa-circle' }}"></i>
            </div>
        </div>

        {{-- Order info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="font-display font-bold text-navy-dark">Informasi Order</h2>
                @if($order->attachment_path)
                    <a href="{{ asset("storage/{$order->attachment_path}") }}" download
                       class="inline-flex items-center gap-2 bg-navy/10 text-navy text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-navy hover:text-white transition-colors">
                        <i class="fas fa-download"></i> Unduh Lampiran
                    </a>
                @endif
            </div>
            <div class="p-6">
                <dl class="space-y-4 text-sm">
                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-gray-500 font-medium">Judul</dt>
                        <dd class="col-span-2 text-navy-dark font-semibold">{{ $order->title }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-gray-500 font-medium">Layanan</dt>
                        <dd class="col-span-2 text-navy-dark">{{ $order->service?->name ?? '—' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-gray-500 font-medium">Deadline</dt>
                        <dd class="col-span-2 {{ $order->deadline->isPast() && !in_array($order->status, ['done','cancelled']) ? 'text-red-600 font-bold' : 'text-navy-dark' }}">
                            {{ $order->deadline->format('d F Y') }}
                            @if($order->deadline->isPast() && !in_array($order->status, ['done','cancelled']))
                                <span class="ml-1 text-xs font-normal text-red-500">(Lewat deadline!)</span>
                            @endif
                        </dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-gray-500 font-medium">Budget Klien</dt>
                        <dd class="col-span-2 text-navy-dark">{{ $order->budget ?? 'Tidak disebutkan' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-gray-500 font-medium">Harga Final</dt>
                        <dd class="col-span-2 text-navy-dark font-bold">
                            {{ $order->price_final ? 'Rp ' . number_format($order->price_final, 0, ',', '.') : 'Belum ditetapkan' }}
                        </dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 pt-3 border-t border-gray-50">
                        <dt class="text-gray-500 font-medium">Deskripsi</dt>
                        <dd class="col-span-2 text-gray-700 leading-relaxed whitespace-pre-line">{{ $order->description }}</dd>
                    </div>
                    @if($order->admin_notes)
                        <div class="grid grid-cols-3 gap-4 pt-3 border-t border-gray-50">
                            <dt class="text-gray-500 font-medium">Catatan Admin</dt>
                            <dd class="col-span-2 text-gray-700 leading-relaxed bg-yellow-50 rounded-xl p-3 text-xs">{{ $order->admin_notes }}</dd>
                        </div>
                    @endif
                    <div class="grid grid-cols-3 gap-4 pt-3 border-t border-gray-50">
                        <dt class="text-gray-500 font-medium">Tanggal Masuk</dt>
                        <dd class="col-span-2 text-gray-500 text-xs">{{ $order->created_at->format('d F Y, H:i') }} WIB</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Client info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="font-display font-bold text-navy-dark">Data Klien</h2>
                <a href="{{ $waUrl }}" target="_blank"
                   class="inline-flex items-center gap-2 bg-green-500 text-white text-xs font-semibold px-4 py-2 rounded-xl hover:bg-green-600 transition-colors">
                    <i class="fab fa-whatsapp text-sm"></i>
                    Chat WA (Template)
                </a>
            </div>
            <div class="p-6">
                <dl class="space-y-4 text-sm">
                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-gray-500 font-medium">Nama</dt>
                        <dd class="col-span-2 text-navy-dark font-semibold">{{ $order->client_name }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-gray-500 font-medium">Email</dt>
                        <dd class="col-span-2">
                            <a href="mailto:{{ $order->client_email }}" class="text-navy hover:text-gold transition-colors">{{ $order->client_email }}</a>
                        </dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <dt class="text-gray-500 font-medium">WhatsApp</dt>
                        <dd class="col-span-2">
                            <a href="https://wa.me/{{ $waPhone }}" target="_blank"
                               class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 hover:bg-green-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                <i class="fab fa-whatsapp"></i> {{ $order->client_phone }}
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    {{-- Right: Actions --}}
    <div class="space-y-5">

        {{-- Update Status Form --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-display font-bold text-navy-dark">Update Status</h2>
            </div>
            <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="p-6 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition bg-white">
                        @foreach($statuses as $key => $val)
                            <option value="{{ $key }}" {{ $order->status === $key ? 'selected' : '' }}>{{ $val['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Harga Final (Rp)</label>
                    <input type="number" name="price_final" value="{{ $order->price_final }}"
                           placeholder="0"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pesan untuk Klien</label>
                    <textarea name="admin_notes" rows="4"
                              placeholder="Pesan update, instruksi tambahan, dll..."
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition resize-none">{{ $order->admin_notes }}</textarea>
                </div>

                <button type="submit"
                        class="w-full bg-navy text-white font-bold py-3 rounded-xl hover:bg-navy-light transition-colors text-sm">
                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        {{-- Tracking link --}}
        <div class="bg-navy-dark rounded-2xl p-5">
            <p class="text-gray-400 text-xs mb-2">Link tracking untuk klien:</p>
            <div class="bg-navy rounded-xl p-3 flex items-center gap-2">
                <span class="text-gold font-mono text-xs truncate flex-1">{{ route('order.track', $order->tracking_token) }}</span>
                <button @click="copyTrackingLink()"
                        class="text-gray-400 hover:text-gold transition-colors flex-shrink-0 p-1">
                    <i :class="copyDone ? 'fas fa-check text-green-400' : 'fas fa-copy'"></i>
                </button>
            </div>
        </div>

        {{-- Navigation --}}
        <a href="{{ route('admin.orders.index') }}"
           class="flex items-center justify-center gap-2 w-full border-2 border-gray-200 text-gray-600 font-semibold py-3 rounded-xl hover:border-navy hover:text-navy transition-all text-sm">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>

        {{-- Delete --}}
        <button @click="deleteModal = true"
                class="flex items-center justify-center gap-2 w-full border-2 border-red-200 text-red-500 font-semibold py-3 rounded-xl hover:bg-red-50 hover:border-red-400 transition-all text-sm">
            <i class="fas fa-trash-alt"></i> Hapus Order
        </button>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteModal = false"></div>

        {{-- Modal --}}
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full p-7 text-center"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
            </div>

            <h3 class="font-display font-bold text-navy-dark text-lg mb-2">Hapus Order?</h3>
            <p class="text-gray-500 text-sm mb-1">Order <span class="font-mono font-bold text-navy">{{ $order->tracking_token }}</span> akan dihapus permanen.</p>
            <p class="text-gray-400 text-xs mb-6">Tindakan ini tidak dapat dibatalkan.</p>

            <div class="flex gap-3">
                <button @click="deleteModal = false"
                        class="flex-1 border-2 border-gray-200 text-gray-600 font-semibold py-2.5 rounded-xl hover:border-gray-300 transition-colors text-sm">
                    Batal
                </button>
                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full bg-red-500 text-white font-bold py-2.5 rounded-xl hover:bg-red-600 transition-colors text-sm">
                        <i class="fas fa-trash-alt mr-1"></i> Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

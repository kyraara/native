@extends('layouts.app')

@section('title', 'Tracking Order #' . $order->tracking_token . ' — NativeCuy')

@section('content')

@php
    $statusConfig = [
        'pending'    => ['bg' => 'bg-amber-100 border-amber-200',   'text' => 'text-amber-800',  'icon' => 'fas fa-clock',       'label' => 'Menunggu Konfirmasi', 'dot' => 'bg-amber-400'],
        'processing' => ['bg' => 'bg-blue-100 border-blue-200',     'text' => 'text-blue-800',   'icon' => 'fas fa-pen-nib',     'label' => 'Sedang Dikerjakan',  'dot' => 'bg-blue-500'],
        'review'     => ['bg' => 'bg-purple-100 border-purple-200', 'text' => 'text-purple-800', 'icon' => 'fas fa-search-plus', 'label' => 'Dalam Review',       'dot' => 'bg-purple-500'],
        'done'       => ['bg' => 'bg-green-100 border-green-200',   'text' => 'text-green-800',  'icon' => 'fas fa-check-double','label' => 'Selesai! 🎉',         'dot' => 'bg-green-500'],
        'cancelled'  => ['bg' => 'bg-red-100 border-red-200',       'text' => 'text-red-800',    'icon' => 'fas fa-times-circle','label' => 'Dibatalkan',         'dot' => 'bg-red-500'],
    ];
    $sc = $statusConfig[$order->status] ?? $statusConfig['pending'];

    $daysLeft  = now()->diffInDays($order->deadline, false);
    $isOverdue = $daysLeft < 0 && !in_array($order->status, ['done', 'cancelled']);
    $isUrgent  = $daysLeft >= 0 && $daysLeft <= 2 && !in_array($order->status, ['done', 'cancelled']);
@endphp

<div class="min-h-screen bg-cream relative overflow-hidden pt-20 pb-16" x-data="trackingPage()">

    {{-- Background --}}
    <div class="absolute top-20 right-0 w-80 h-80 bg-gold/6 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-64 h-64 bg-navy/5 rounded-full blur-3xl pointer-events-none"></div>

    {{-- Toast --}}
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-navy-dark border border-gold/20 text-white px-5 py-3 rounded-2xl shadow-2xl whitespace-nowrap"
         style="display: none;">
        <i class="fas fa-check-circle text-emerald-400"></i>
        <span class="text-sm font-medium" x-text="toast.message"></span>
    </div>

    <div class="max-w-xl mx-auto px-4 sm:px-6 relative">

        {{-- Success flash --}}
        @if(session('success'))
            <div class="bg-green-500 text-white rounded-2xl px-5 py-4 mb-6 flex items-start gap-3 shadow-lg" data-aos="fade-down">
                <i class="fas fa-check-circle text-xl flex-shrink-0 mt-0.5"></i>
                <div>
                    <p class="font-bold text-sm">Order berhasil dikirim!</p>
                    <p class="text-xs opacity-90 mt-0.5">Simpan kode tracking kamu ya!</p>
                </div>
            </div>
        @endif

        {{-- Page header --}}
        <div class="text-center mb-6" data-aos="fade-up">
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-navy-dark mb-1">
                Tracking <span class="font-script text-gold">Order</span>
            </h1>
            <p class="text-gray-500 text-sm">Status real-time pengerjaan tugasmu</p>
        </div>

        {{-- Status badge utama --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-5" data-aos="fade-up">
            <div class="inline-flex items-center gap-3 {{ $sc['bg'] }} {{ $sc['text'] }} border px-5 py-3 rounded-2xl font-bold shadow-sm">
                <div class="w-2.5 h-2.5 rounded-full {{ $sc['dot'] }} animate-pulse flex-shrink-0"></div>
                <i class="{{ $sc['icon'] }} text-sm"></i>
                <span class="text-sm sm:text-base">{{ $sc['label'] }}</span>
            </div>

            @if($isOverdue)
                <div class="inline-flex items-center gap-2 bg-red-100 border border-red-200 text-red-700 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold">
                    <i class="fas fa-exclamation-triangle"></i>
                    Deadline terlewat {{ abs($daysLeft) }} hari yang lalu
                </div>
            @elseif($isUrgent)
                <div class="inline-flex items-center gap-2 bg-amber-100 border border-amber-200 text-amber-700 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold">
                    <i class="fas fa-fire"></i>
                    Deadline {{ $daysLeft === 0 ? 'hari ini!' : "dalam {$daysLeft} hari" }}
                </div>
            @elseif($order->status === 'processing' && $daysLeft > 0)
                <div class="inline-flex items-center gap-2 bg-green-100 border border-green-200 text-green-700 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold">
                    <i class="fas fa-calendar-check"></i>
                    Estimasi selesai {{ $daysLeft }} hari lagi
                </div>
            @endif
        </div>

        {{-- Token card --}}
        <div class="bg-navy-dark rounded-2xl border border-gold/20 p-5 mb-5 flex items-center justify-between gap-4" data-aos="fade-up">
            <div>
                <p class="text-gray-400 text-xs mb-1">Kode Tracking</p>
                <p class="font-display text-xl sm:text-2xl font-extrabold text-gold tracking-widest font-mono">{{ $order->tracking_token }}</p>
            </div>
            <button @click="copyToken('{{ $order->tracking_token }}')"
                    class="flex items-center gap-2 border px-3 sm:px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 flex-shrink-0"
                    :class="copied
                        ? 'border-green-400 bg-green-400/10 text-green-400'
                        : 'border-navy text-gray-300 hover:border-gold hover:text-gold'">
                <i :class="copied ? 'fas fa-check' : 'fas fa-copy'" class="text-xs"></i>
                <span x-text="copied ? 'Disalin!' : 'Salin'"></span>
            </button>
        </div>

        {{-- Timeline --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-5" data-aos="fade-up" data-aos-delay="80">
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-display font-bold text-navy-dark text-sm sm:text-base">Progress Pengerjaan</h2>
                <span class="text-gray-400 text-xs">Update otomatis</span>
            </div>

            <div class="p-5 sm:p-6">
                @php
                    $steps = [
                        ['key' => 'pending',    'label' => 'Order Diterima',     'icon' => 'fas fa-inbox',       'desc' => 'Order sudah masuk. Admin sedang mengkonfirmasi.'],
                        ['key' => 'processing', 'label' => 'Sedang Dikerjakan',  'icon' => 'fas fa-pen-nib',     'desc' => 'Expert kami sedang mengerjakan tugasmu dengan teliti.'],
                        ['key' => 'review',     'label' => 'Review & Finishing', 'icon' => 'fas fa-search-plus', 'desc' => 'Hasil sedang dicek kualitasnya sebelum dikirimkan.'],
                        ['key' => 'done',       'label' => 'Selesai! 🎉',        'icon' => 'fas fa-check-circle','desc' => 'Tugas selesai! Admin akan segera menghubungimu.'],
                    ];
                    $statusOrder = ['pending' => 0, 'processing' => 1, 'review' => 2, 'done' => 3];
                    $currentIdx  = $statusOrder[$order->status] ?? 0;
                @endphp

                @if($order->status === 'cancelled')
                    <div class="flex items-center gap-4 bg-red-50 border border-red-100 rounded-2xl p-4 sm:p-5">
                        <div class="w-11 h-11 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-times-circle text-red-500 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-red-700 font-display text-sm">Order Dibatalkan</p>
                            <p class="text-red-600 text-xs mt-0.5">Hubungi kami untuk info lebih lanjut.</p>
                        </div>
                    </div>
                @else
                    <div class="space-y-0">
                        @foreach($steps as $i => $step)
                            @php
                                $isDone    = $i < $currentIdx;
                                $isCurrent = $i === $currentIdx;
                                $isPending = $i > $currentIdx;
                            @endphp
                            <div class="flex items-start gap-3 sm:gap-4 {{ !$loop->last ? 'pb-5' : '' }} relative">
                                @if(!$loop->last)
                                    <div class="absolute left-4 sm:left-5 top-9 sm:top-10 bottom-0 w-0.5 {{ ($isDone || $isCurrent) ? 'bg-gradient-to-b from-gold to-gold/20' : 'bg-gray-100' }}"></div>
                                @endif

                                {{-- Circle --}}
                                <div class="relative z-10 flex-shrink-0">
                                    @if($isDone)
                                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-emerald-400 flex items-center justify-center shadow-sm shadow-emerald-400/30">
                                            <i class="fas fa-check text-white text-xs sm:text-sm"></i>
                                        </div>
                                    @elseif($isCurrent)
                                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gold flex items-center justify-center shadow-md shadow-gold/30 ring-4 ring-gold/20">
                                            <i class="{{ $step['icon'] }} text-navy-dark text-xs sm:text-sm"></i>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="{{ $step['icon'] }} text-gray-300 text-xs sm:text-sm"></i>
                                        </div>
                                    @endif
                                </div>

                                {{-- Label --}}
                                <div class="flex-1 pt-1.5 sm:pt-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-display font-bold text-xs sm:text-sm
                                            {{ $isDone ? 'text-emerald-600' : ($isCurrent ? 'text-navy-dark' : 'text-gray-300') }}">
                                            {{ $step['label'] }}
                                        </h3>
                                        @if($isCurrent)
                                            <span class="bg-gold/10 text-gold text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">Sekarang</span>
                                        @elseif($isDone)
                                            <span class="bg-emerald-50 text-emerald-600 text-xs font-bold px-2 py-0.5 rounded-full">✓ Done</span>
                                        @endif
                                    </div>
                                    @if(!$isPending)
                                        <p class="text-gray-400 text-xs mt-1 leading-relaxed">{{ $step['desc'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Order detail --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-5" data-aos="fade-up" data-aos-delay="100">
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100">
                <h2 class="font-display font-bold text-navy-dark text-sm sm:text-base">Detail Order</h2>
            </div>
            <div class="divide-y divide-gray-50 text-sm">
                @php
                    $details = [
                        ['label' => 'Layanan',    'value' => $order->service?->name ?? 'Lainnya'],
                        ['label' => 'Judul',      'value' => $order->title, 'bold' => true],
                        ['label' => 'Deadline',   'value' => $order->deadline->format('d F Y'), 'extra' => $isOverdue ? 'text-red-600 font-bold' : ($isUrgent ? 'text-amber-600 font-semibold' : '')],
                        ['label' => 'Order',      'value' => $order->created_at->format('d M Y, H:i') . ' WIB'],
                        ['label' => 'Nama',       'value' => $order->client_name],
                    ];
                    if ($order->price_final) {
                        $details[] = ['label' => 'Harga Final', 'value' => 'Rp ' . number_format($order->price_final, 0, ',', '.'), 'bold' => true];
                    }
                @endphp
                @foreach($details as $detail)
                    <div class="flex justify-between items-start px-5 sm:px-6 py-3 sm:py-3.5 gap-4">
                        <span class="text-gray-400 text-xs sm:text-sm flex-shrink-0 w-20 sm:w-24">{{ $detail['label'] }}</span>
                        <span class="font-medium text-right text-xs sm:text-sm {{ $detail['extra'] ?? '' }} {{ ($detail['bold'] ?? false) ? 'text-navy-dark font-bold' : 'text-navy-dark' }}">
                            {{ $detail['value'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Admin notes --}}
        @if($order->admin_notes)
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-5" data-aos="fade-up" data-aos-delay="120">
                <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <i class="fas fa-comment-dots text-gold text-sm"></i>
                    <h2 class="font-display font-bold text-navy-dark text-sm sm:text-base">Pesan dari Admin</h2>
                </div>
                <div class="p-5 sm:p-6">
                    <p class="text-gray-600 text-xs sm:text-sm leading-relaxed">{{ $order->admin_notes }}</p>
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-stretch gap-3" data-aos="fade-up" data-aos-delay="140">
            <a href="https://wa.me/{{ \App\Models\Setting::get('wa_number', '6281234567890') }}?text={{ urlencode('Halo NativeCuy, saya mau tanya tentang order #' . $order->tracking_token) }}"
               target="_blank"
               class="flex-1 flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-5 py-3.5 rounded-2xl transition-colors text-sm shadow-md">
                <i class="fab fa-whatsapp text-lg"></i>
                Hubungi Admin
            </a>
            <a href="{{ route('order.trackSearch') }}"
               class="flex-1 flex items-center justify-center gap-2 border-2 border-gray-200 text-gray-600 hover:border-navy hover:text-navy font-semibold px-5 py-3.5 rounded-2xl transition-all text-sm">
                <i class="fas fa-search text-xs"></i>
                Cek Order Lain
            </a>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
    function trackingPage() {
        return {
            copied: false,
            toast: { show: false, message: '' },

            copyToken(token) {
                navigator.clipboard.writeText(token)
                    .then(() => this.onCopied())
                    .catch(() => {
                        const el = document.createElement('textarea');
                        el.value = token;
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        this.onCopied();
                    });
            },

            onCopied() {
                this.copied = true;
                this.showToast('Kode tracking berhasil disalin!');
                setTimeout(() => this.copied = false, 2500);
            },

            showToast(message) {
                this.toast = { show: true, message };
                setTimeout(() => this.toast.show = false, 3000);
            },
        };
    }
</script>
@endpush

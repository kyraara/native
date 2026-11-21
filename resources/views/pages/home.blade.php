@extends('layouts.app')
@section('title', 'NativeCuy — Bantuan Tugas, Presentasi, Laporan & Website')
@section('content')
@include('components.public-hero')
<div class="native-classic">
@if($services->isNotEmpty())
<div class="legacy-ticker" x-data="publicMarquee" :class="{ 'is-paused': paused }">
    <div class="legacy-ticker-track">
        <nav class="legacy-ticker-group" aria-label="Pilihan layanan NativeCuy">@foreach($services as $service)<a href="{{ route('order.create', ['service' => $service->id]) }}">{{ $service->name }} <svg viewBox="0 0 12 12" aria-hidden="true"><path d="M6 0 8 4 12 6 8 8 6 12 4 8 0 6 4 4Z" fill="currentColor"/></svg></a>@endforeach</nav>
        <div class="legacy-ticker-group legacy-ticker-copy" aria-hidden="true" inert>@foreach($services as $service)<span>{{ $service->name }} <svg viewBox="0 0 12 12"><path d="M6 0 8 4 12 6 8 8 6 12 4 8 0 6 4 4Z" fill="currentColor"/></svg></span>@endforeach</div>
    </div>
</div>
@endif

@include('components.public-stats')

@include('components.public-catalog')

@include('components.public-process')

{{-- ===================== KEUNGGULAN ===================== --}}
<section class="relative py-16 sm:py-20 bg-cream overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <div data-aos="fade-right">
                <h2 class="font-display text-2xl sm:text-3xl md:text-4xl font-extrabold text-navy-dark mt-2 mb-5 sm:mb-6">
                    Bukan Sekadar Joki,<br>
                    Tapi Partner <span class="font-script text-gold">Akademik</span> Kamu
                </h2>
                <p class="text-gray-500 leading-relaxed mb-6 sm:mb-8 text-sm sm:text-base">
                    Kami paham tekanan deadline dan standar kampus. Nggak cuma ngerjain, tapi juga mastiin hasilnya worth it buat kamu.
                </p>
                <a href="https://wa.me/{{ $publicContact['wa_number'] }}?text=Halo%20NativeCuy"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 bg-navy text-white px-5 sm:px-6 py-3 rounded-xl font-bold hover:bg-navy-light transition-colors text-sm sm:text-base">
                    <i class="fab fa-whatsapp text-emerald-400"></i>
                    Chat Sekarang
                </a>
            </div>

            <div class="space-y-4 sm:space-y-5" data-aos="fade-left" data-aos-delay="100">
                @php
                    $features = [
                        ['icon' => 'bolt',      'color' => 'bg-amber-50 text-amber-600',   'title' => 'Pengerjaan Cepat',   'desc' => 'Deadline mendesak? Ceritakan dulu agar admin bisa memastikan waktu pengerjaan.'],
                        ['icon' => 'shield','color' => 'bg-emerald-50 text-emerald-600','title' => 'Sesuai Kebutuhanmu',   'desc' => 'Detail, format, dan referensi dibahas di awal agar arah pengerjaannya jelas.'],
                        ['icon' => 'revision',  'color' => 'bg-blue-50 text-blue-600',     'title' => 'Bisa Diskusi & Revisi',      'desc' => 'Sampaikan masukanmu. Cakupan revisi disepakati bersama saat konsultasi.'],
                        ['icon' => 'phone-lock',      'color' => 'bg-purple-50 text-purple-600', 'title' => 'Identitas Aman',     'desc' => 'Informasi kontak dan file order hanya dapat diakses admin. Simpan kode tracking secara pribadi.'],
                        ['icon' => 'headset',   'color' => 'bg-rose-50 text-rose-600',     'title' => 'Mudah Dihubungi',    'desc' => 'Tanya lewat WhatsApp dan pantau pesan admin dari halaman tracking.'],
                    ];
                @endphp
                @foreach($features as $i => $f)
                    <div class="flex items-start gap-4 p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow"
                         data-aos="fade-left" data-aos-delay="{{ 100 + $i * 70 }}">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl {{ $f['color'] }} flex items-center justify-center flex-shrink-0">
                            <x-public-icon :name="$f['icon']"/>
                        </div>
                        <div>
                            <h4 class="font-display font-bold text-navy-dark text-sm mb-0.5">{{ $f['title'] }}</h4>
                            <p class="text-gray-500 text-xs leading-relaxed">{{ $f['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    {{-- Double wave → navy --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 60 L0 35 C240 5 480 55 720 30 C960 5 1200 55 1440 25 L1440 60 Z" fill="#1B3A6E"/>
        </svg>
    </div>
</section>

@include('components.public-testimonials')

@include('components.public-faq')

{{-- ===================== CTA BANNER ===================== --}}
<section class="relative py-16 sm:py-20 bg-cream overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8" data-aos="fade-up">
        <div class="relative overflow-hidden rounded-3xl bg-navy-dark shadow-2xl shadow-navy-dark/30 text-center px-8 sm:px-14 py-14 sm:py-16">

            {{-- Decorative glows inside card --}}
            <div class="absolute top-0 right-0 w-64 h-64 bg-gold/8 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-navy-light/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-32 bg-gold/5 rounded-full blur-3xl pointer-events-none"></div>

            {{-- Grid pattern inside card --}}
            <div class="absolute inset-0 bg-[linear-gradient(rgba(245,185,20,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(245,185,20,0.04)_1px,transparent_1px)] bg-[size:40px_40px] pointer-events-none rounded-3xl"></div>

            <div class="relative z-10">
                <div class="mb-5 flex justify-center">
                    <img src="{{ asset('images/nativecuy_icon_192x192.png') }}" alt="NativeCuy mascot" class="w-20 h-20 sm:w-24 sm:h-24 object-contain drop-shadow-2xl animate-float">
                </div>
                <h2 class="font-display text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-4 leading-tight">
                    Siap Bikin Tugas <br><span class="font-script text-gold">Beres Sekarang?</span>
                </h2>
                <p class="text-gray-400 text-sm sm:text-base lg:text-lg mb-8 sm:mb-10 max-w-lg mx-auto">
                    Jangan tunggu sampai H-1 deadline baru panik. Konsultasi dulu gratis!
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                    <a href="{{ route('order.create') }}"
                       class="w-full sm:w-auto btn-gold inline-flex items-center justify-center gap-2 px-8 sm:px-10 py-3.5 sm:py-4 rounded-full text-navy-dark font-extrabold text-base shadow-xl shadow-gold/20 hover:scale-105 transition-transform">
                        <i class="fas fa-bolt"></i> Order Sekarang
                    </a>
                    <a href="https://wa.me/{{ $publicContact['wa_number'] }}" target="_blank" rel="noopener noreferrer"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 sm:px-8 py-3.5 sm:py-4 rounded-full border-2 border-white/20 text-white font-semibold hover:border-gold/60 hover:text-gold transition-all text-sm sm:text-base">
                        <i class="fab fa-whatsapp text-emerald-400"></i>
                        WhatsApp Kami
                    </a>
                </div>
            </div>
        </div>
    </div>
    {{-- Soft arc → footer --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 60 L0 45 Q360 10 720 35 Q1080 60 1440 30 L1440 60 Z" fill="#0D1F3C"/>
        </svg>
    </div>
</section>

</div>
@endsection

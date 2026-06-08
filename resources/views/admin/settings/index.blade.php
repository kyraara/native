@extends('layouts.admin')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan Website')
@section('page-subtitle', 'Kelola konten, statistik, dan informasi kontak NativeCuy')

@section('content')

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Statistik Website --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-bar text-blue-600 text-sm"></i>
                </div>
                <div>
                    <h2 class="font-display font-bold text-navy-dark text-sm">Statistik Website</h2>
                    <p class="text-gray-400 text-xs">Ditampilkan di section counter landing page</p>
                </div>
            </div>
            <div class="p-6 space-y-4">
                @php
                    $statFields = [
                        ['key' => 'total_orders',  'label' => 'Order Selesai',  'icon' => 'fas fa-check-circle', 'color' => 'text-navy',    'placeholder' => 'Contoh: 500 atau 500+'],
                        ['key' => 'happy_clients', 'label' => 'Klien Puas',     'icon' => 'fas fa-heart',        'color' => 'text-red-500', 'placeholder' => 'Contoh: 350 atau 350+'],
                        ['key' => 'subjects',      'label' => 'Mata Kuliah',    'icon' => 'fas fa-book',         'color' => 'text-gold',    'placeholder' => 'Contoh: 50 atau 50+'],
                        ['key' => 'rating',        'label' => 'Rating Bintang', 'icon' => 'fas fa-star',         'color' => 'text-gold',    'placeholder' => 'Contoh: 4.9'],
                    ];
                @endphp

                @foreach($statFields as $field)
                    <div>
                        <label class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            <i class="{{ $field['icon'] }} {{ $field['color'] }} text-xs"></i>
                            {{ $field['label'] }}
                        </label>
                        <input type="text" name="{{ $field['key'] }}"
                               value="{{ $settings[$field['key']] ?? '' }}"
                               placeholder="{{ $field['placeholder'] }}"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition @error($field['key']) border-red-300 @enderror">
                        @error($field['key'])
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <p class="text-gray-400 text-xs bg-blue-50 rounded-xl p-3 leading-relaxed">
                    <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                    Nilai angka (misal: <strong>500</strong>) akan ditampilkan dengan animasi counter. Nilai teks (misal: <strong>500+</strong>) ditampilkan statis.
                </p>
            </div>
        </div>

        {{-- Informasi Kontak --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-address-book text-green-600 text-sm"></i>
                </div>
                <div>
                    <h2 class="font-display font-bold text-navy-dark text-sm">Informasi Kontak</h2>
                    <p class="text-gray-400 text-xs">Digunakan di navbar, footer, dan link WhatsApp</p>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        <i class="fab fa-whatsapp text-emerald-500 text-xs"></i>
                        Nomor WhatsApp
                    </label>
                    <div class="flex rounded-xl overflow-hidden border border-gray-200 focus-within:ring-2 focus-within:ring-navy/20 focus-within:border-navy transition">
                        <span class="bg-gray-50 px-3 flex items-center text-gray-500 text-sm border-r border-gray-200">+</span>
                        <input type="text" name="wa_number"
                               value="{{ $settings['wa_number'] ?? '' }}"
                               placeholder="6281234567890"
                               class="flex-1 px-4 py-2.5 text-sm focus:outline-none bg-white @error('wa_number') bg-red-50 @enderror">
                    </div>
                    <p class="text-gray-400 text-xs mt-1">Format: kode negara tanpa + (contoh: 62812xxxxx)</p>
                    @error('wa_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        <i class="fas fa-envelope text-blue-500 text-xs"></i>
                        Email
                    </label>
                    <input type="email" name="email"
                           value="{{ $settings['email'] ?? '' }}"
                           placeholder="order@nativecuy.id"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition @error('email') border-red-300 @enderror">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        <i class="fab fa-instagram text-pink-500 text-xs"></i>
                        Instagram
                    </label>
                    <div class="flex rounded-xl overflow-hidden border border-gray-200 focus-within:ring-2 focus-within:ring-navy/20 focus-within:border-navy transition">
                        <span class="bg-gray-50 px-3 flex items-center text-gray-500 text-sm border-r border-gray-200">@</span>
                        <input type="text" name="instagram"
                               value="{{ ltrim($settings['instagram'] ?? '', '@') }}"
                               placeholder="nativecuy.id"
                               class="flex-1 px-4 py-2.5 text-sm focus:outline-none bg-white">
                    </div>
                    @error('instagram') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        <i class="fas fa-map-marker-alt text-red-400 text-xs"></i>
                        Lokasi (Footer)
                    </label>
                    <input type="text" name="location"
                           value="{{ $settings['location'] ?? 'Palembang, Sumatera Selatan' }}"
                           placeholder="Palembang, Sumatera Selatan"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition">
                </div>
            </div>
        </div>

    </div>

    {{-- Preview card --}}
    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 bg-gold/10 rounded-xl flex items-center justify-center">
                <i class="fas fa-eye text-gold text-sm"></i>
            </div>
            <div>
                <h2 class="font-display font-bold text-navy-dark text-sm">Preview Statistik</h2>
                <p class="text-gray-400 text-xs">Tampilan saat ini di landing page</p>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @php
                    $previewStats = [
                        ['key' => 'total_orders',  'label' => 'Order Selesai', 'icon' => 'fas fa-check-circle', 'color' => 'text-navy',    'bg' => 'bg-navy/10'],
                        ['key' => 'happy_clients', 'label' => 'Klien Puas',    'icon' => 'fas fa-heart',        'color' => 'text-red-500', 'bg' => 'bg-red-50'],
                        ['key' => 'subjects',      'label' => 'Mata Kuliah',   'icon' => 'fas fa-book',         'color' => 'text-gold',    'bg' => 'bg-gold/10'],
                        ['key' => 'rating',        'label' => 'Rating',        'icon' => 'fas fa-star',         'color' => 'text-gold',    'bg' => 'bg-gold/10'],
                    ];
                @endphp
                @foreach($previewStats as $ps)
                    <div class="text-center p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl {{ $ps['bg'] }} mb-3">
                            <i class="{{ $ps['icon'] }} {{ $ps['color'] }} text-lg"></i>
                        </div>
                        <div class="text-2xl font-extrabold font-display text-navy-dark">
                            {{ $settings[$ps['key']] ?? '0' }}
                            @if(is_numeric($settings[$ps['key']] ?? ''))
                                <span class="text-sm">@if($ps['key'] === 'rating')★@else+@endif</span>
                            @endif
                        </div>
                        <p class="text-gray-500 text-xs mt-1">{{ $ps['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Save button --}}
    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.dashboard') }}"
           class="px-5 py-2.5 border-2 border-gray-200 text-gray-600 font-semibold rounded-xl hover:border-gray-300 transition-colors text-sm">
            Batal
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 bg-navy text-white font-bold px-7 py-2.5 rounded-xl hover:bg-navy-light transition-colors shadow-sm text-sm">
            <i class="fas fa-save"></i>
            Simpan Pengaturan
        </button>
    </div>
</form>

@endsection

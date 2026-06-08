@php
    $wa = \App\Models\Setting::get('wa_number', '6281234567890');
    $email = \App\Models\Setting::get('email', 'order@nativecuy.id');
    $ig = \App\Models\Setting::get('instagram', '@nativecuy.id');
@endphp

<footer class="bg-gray-950 text-gray-300 border-t border-gray-900 relative overflow-hidden">

    {{-- Radial glow effects --}}
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-gold/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-0 left-1/3 w-80 h-80 bg-navy/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 right-1/4 w-64 h-64 bg-indigo-600/5 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-8">

            {{-- Brand column --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-block mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="NativeCuy" class="h-12 w-auto">
                </a>
                <p class="text-gray-400 text-sm leading-relaxed max-w-xs">
                    Solusi cerdas tugas kuliah & digital. Dikerjakan teliti, bergaransi, tepat waktu.
                </p>
                <div class="flex items-center gap-3 mt-6">
                    <a href="https://wa.me/{{ $wa }}?text=Halo%20NativeCuy%2C%20saya%20mau%20konsultasi"
                       target="_blank" rel="noopener noreferrer"
                       class="w-10 h-10 rounded-xl bg-gray-900 border border-gray-800 flex items-center justify-center text-gray-400 hover:border-emerald-500/50 hover:bg-emerald-500/10 hover:text-emerald-400 transition-all duration-300 hover:scale-110">
                        <i class="fab fa-whatsapp text-sm"></i>
                    </a>
                    <a href="https://instagram.com/{{ ltrim($ig, '@') }}"
                       target="_blank" rel="noopener noreferrer"
                       class="w-10 h-10 rounded-xl bg-gray-900 border border-gray-800 flex items-center justify-center text-gray-400 hover:border-pink-500/50 hover:bg-pink-500/10 hover:text-pink-400 transition-all duration-300 hover:scale-110">
                        <i class="fab fa-instagram text-sm"></i>
                    </a>
                    <a href="mailto:{{ $email }}"
                       class="w-10 h-10 rounded-xl bg-gray-900 border border-gray-800 flex items-center justify-center text-gray-400 hover:border-blue-400/50 hover:bg-blue-400/10 hover:text-blue-400 transition-all duration-300 hover:scale-110">
                        <i class="fas fa-envelope text-sm"></i>
                    </a>
                </div>
            </div>

            {{-- Layanan --}}
            <div>
                <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-5">Layanan Kami</h4>
                <ul class="space-y-3 text-sm">
                    @foreach(['Tugas & Laporan', 'Makalah & Skripsi', 'Presentasi PPT', 'Coding & Program', 'Pembuatan Website'] as $item)
                        <li>
                            <a href="{{ route('order.create') }}"
                               class="text-gray-400 hover:text-gold transition-colors duration-200">
                                {{ $item }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Perusahaan --}}
            <div>
                <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-5">Perusahaan</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('home') }}#cara-kerja" class="text-gray-400 hover:text-gold transition-colors">Cara Kerja</a></li>
                    <li><a href="{{ route('home') }}#testimoni" class="text-gray-400 hover:text-gold transition-colors">Testimoni</a></li>
                    <li><a href="{{ route('home') }}#faq" class="text-gray-400 hover:text-gold transition-colors">FAQ</a></li>
                    <li>
                        <a href="{{ route('order.trackSearch') }}"
                           class="text-gray-400 hover:text-gold transition-colors inline-flex items-center gap-1.5">
                            <i class="fas fa-search text-xs text-gold/60"></i>
                            Tracking Order
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Kontak --}}
            <div>
                <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-5">Hubungi Kami</h4>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-900 border border-gray-800 flex items-center justify-center text-emerald-400 flex-shrink-0">
                            <i class="fab fa-whatsapp text-xs"></i>
                        </div>
                        <a href="https://wa.me/{{ $wa }}" target="_blank"
                           class="text-gray-400 hover:text-emerald-400 transition-colors text-xs">
                            +{{ $wa }}
                        </a>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-900 border border-gray-800 flex items-center justify-center text-pink-400 flex-shrink-0">
                            <i class="fab fa-instagram text-xs"></i>
                        </div>
                        <a href="https://instagram.com/{{ ltrim($ig, '@') }}" target="_blank"
                           class="text-gray-400 hover:text-pink-400 transition-colors text-xs">
                            {{ $ig }}
                        </a>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-900 border border-gray-800 flex items-center justify-center text-blue-400 flex-shrink-0">
                            <i class="fas fa-envelope text-xs"></i>
                        </div>
                        <a href="mailto:{{ $email }}"
                           class="text-gray-400 hover:text-blue-400 transition-colors text-xs">
                            {{ $email }}
                        </a>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-900 border border-gray-800 flex items-center justify-center text-gold/60 flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-xs"></i>
                        </div>
                        <span class="text-gray-500 text-xs">Palembang, Sumatera Selatan</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-16 pt-8 border-t border-gray-900 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-gray-600">&copy; {{ date('Y') }} NativeCuy. All rights reserved.</p>
            <p class="text-xs text-gray-700 flex items-center gap-1.5">
                Dibuat dengan <span class="text-red-500 animate-pulse">❤️</span> untuk mahasiswa Indonesia
            </p>
        </div>
    </div>
</footer>

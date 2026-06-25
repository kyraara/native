@extends('layouts.app')

@section('title', 'NativeCuy — Jasa Joki Tugas, Laporan, Makalah, PPT & Web Profesional')

@section('content')

{{-- ===================== HERO ===================== --}}
<section class="relative min-h-screen bg-hero-pattern overflow-x-hidden overflow-y-visible flex items-center pt-16">

    {{-- Background orbs --}}
    <div class="absolute top-24 right-8 w-64 h-64 bg-gold/8 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-24 left-4 w-48 h-48 bg-navy-light/20 rounded-full blur-3xl pointer-events-none"></div>
    {{-- Grid pattern --}}
    <div class="absolute inset-0 bg-[linear-gradient(rgba(245,185,20,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(245,185,20,0.03)_1px,transparent_1px)] bg-[size:48px_48px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-16 lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center">

            {{-- Left: Copy --}}
            <div class="lg:col-span-7" data-aos="fade-right" data-aos-duration="700">

                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 bg-gold/10 border border-gold/30 rounded-full px-4 py-1.5 mb-6">
                    <span class="w-2 h-2 rounded-full bg-gold animate-pulse"></span>
                    <span class="text-gold text-xs font-semibold tracking-wide">TERPERCAYA SEJAK 2022</span>
                </div>

                {{-- Headline --}}
                <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-extrabold text-white leading-tight mb-5">
                    Tugas Beres,<br>
                    Nilai <span class="font-script text-gold">Oke</span>
                    <img src="{{ asset('images/nativecuy_icon_64x64.png') }}" alt="Cuy-AI" class="inline-block w-9 h-9 sm:w-10 sm:h-10 lg:w-12 lg:h-12 xl:w-14 xl:h-14 object-contain align-middle -mt-1 drop-shadow-md">
                </h1>

                <p class="text-gray-300 text-base sm:text-lg leading-relaxed mb-8 max-w-xl">
                    Jasa pengerjaan tugas, laporan, makalah, PPT, hingga website. Dikerjakan expert, tepat waktu, hasil berkualitas.
                </p>

                {{-- CTAs --}}
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-10">
                    <a href="https://wa.me/{{ \App\Models\Setting::get('wa_number', '6281234567890') }}?text=Halo%20NativeCuy%2C%20saya%20mau%20konsultasi%20dulu"
                       target="_blank"
                       class="inline-flex items-center justify-center gap-2.5 bg-emerald-500 hover:bg-emerald-400 text-white font-bold px-7 py-3.5 rounded-2xl shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:scale-105 transition-all duration-200 text-sm sm:text-base">
                        <i class="fab fa-whatsapp text-lg"></i>
                        Konsultasi Gratis via WA
                    </a>
                    <a href="{{ route('order.create') }}"
                       class="inline-flex items-center justify-center gap-2 border-2 border-white/25 text-white font-semibold px-7 py-3.5 rounded-2xl hover:border-gold hover:text-gold transition-all duration-200 text-sm sm:text-base">
                        Isi Form Order
                        <i class="fas fa-arrow-right text-gold text-xs"></i>
                    </a>
                </div>

                {{-- Trust badges --}}
                <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                    <div class="flex items-center gap-2 bg-white/8 backdrop-blur-sm border border-white/15 rounded-full px-4 py-2">
                        <i class="fas fa-shield-alt text-emerald-400 text-xs"></i>
                        <span class="text-gray-300 text-xs font-medium">Anti Plagiarisme</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/8 backdrop-blur-sm border border-white/15 rounded-full px-4 py-2">
                        <i class="fas fa-sync-alt text-blue-400 text-xs"></i>
                        <span class="text-gray-300 text-xs font-medium">Revisi Gratis</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/8 backdrop-blur-sm border border-white/15 rounded-full px-4 py-2">
                        <i class="fas fa-clock text-gold text-xs"></i>
                        <span class="text-gray-300 text-xs font-medium">Fast Respon</span>
                    </div>
                </div>
            </div>

            {{-- Right: Terminal mockup --}}
            <div class="hidden lg:block lg:col-span-5 relative" data-aos="fade-left" data-aos-duration="700" data-aos-delay="100">

                {{-- Glow behind --}}
                <div class="absolute inset-4 bg-gradient-to-tr from-gold/10 to-navy-light/10 rounded-3xl blur-2xl"></div>

                <div class="relative bg-gray-950 border border-gray-800/80 rounded-3xl shadow-2xl overflow-hidden"
                     x-data="terminalMockup()" x-init="start()">

                    {{-- Window chrome --}}
                    <div class="flex items-center justify-between px-5 py-3.5 bg-gray-900 border-b border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/90"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/90"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/90"></div>
                        </div>
                        <div class="text-xs text-gray-500 font-mono flex items-center gap-1.5">
                            <i class="fas fa-terminal text-gold/60 text-xs"></i>
                            nativecuy_process.sh
                        </div>
                        {{-- Owl badge --}}
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                            <span class="text-xs text-gray-500 font-mono">running</span>
                        </div>
                    </div>

                    {{-- Terminal content --}}
                    <div class="p-6 space-y-5 font-mono text-xs min-h-72">

                        <div class="text-gold/80">$ sh nativecuy_process.sh --order=NC-2026</div>

                        <div class="space-y-4">
                            @php
                                $termSteps = [
                                    ['title' => 'Menerima Brief Tugas',      'desc' => 'Sistem menganalisis instruksi & deadline...'],
                                    ['title' => 'Menentukan Joki Terbaik',   'desc' => 'Mencocokkan keahlian dengan tim expert...'],
                                    ['title' => 'Proses Pengerjaan',         'desc' => 'Kode ditulis bersih, rapi, dan berstruktur...'],
                                    ['title' => 'Quality & Plagiarism Check','desc' => 'Memastikan hasil 100% orisinal & sesuai...'],
                                    ['title' => 'Tugas Siap Dikirim! 🎉',    'desc' => 'File dikirim aman, garansi revisi aktif...'],
                                ];
                            @endphp

                            @foreach($termSteps as $ti => $ts)
                                <div class="flex items-start gap-3.5 transition-opacity duration-300"
                                     :class="{{ $ti }} > activeStep ? 'opacity-30' : 'opacity-100'">
                                    {{-- Icon --}}
                                    <div class="mt-0.5 flex-shrink-0">
                                        <template x-if="{{ $ti }} < activeStep">
                                            <i class="fas fa-check-circle text-emerald-400"></i>
                                        </template>
                                        <template x-if="{{ $ti }} === activeStep">
                                            <div class="w-4 h-4 rounded-full border-2 border-gold border-t-transparent animate-spin"></div>
                                        </template>
                                        <template x-if="{{ $ti }} > activeStep">
                                            <div class="w-4 h-4 rounded-full border border-gray-700 bg-gray-900"></div>
                                        </template>
                                    </div>
                                    {{-- Text --}}
                                    <div>
                                        <div class="font-bold"
                                             :class="{{ $ti }} === activeStep ? 'text-gold' : ({{ $ti }} < activeStep ? 'text-gray-300' : 'text-gray-600')">
                                            {{ $ts['title'] }}
                                        </div>
                                        <div x-show="{{ $ti }} === activeStep"
                                             class="text-gray-400 font-sans text-xs mt-0.5 leading-relaxed">
                                            {{ $ts['desc'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Progress bar --}}
                        <div class="pt-4 border-t border-gray-800 space-y-2">
                            <div class="flex justify-between text-gray-500 font-sans text-xs">
                                <span>Task Progress</span>
                                <span class="text-gold font-bold" x-text="progress + '%'"></span>
                            </div>
                            <div class="w-full h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-gold to-amber-400 rounded-full transition-all duration-700 ease-out"
                                     :style="'width:' + progress + '%'"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom wave → gold marquee --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 60L1440 60L1440 20C1200 55 900 0 600 18C300 36 0 0 0 20Z" fill="#F5B914"/>
        </svg>
    </div>
</section>

{{-- ===================== MARQUEE TICKER ===================== --}}
<section class="bg-gold overflow-hidden">
    <div class="py-3.5 flex overflow-hidden">
        <div class="flex items-center gap-8 whitespace-nowrap" style="animation: marquee 25s linear infinite; display: flex;">
            @foreach(array_merge(['Tugas Kuliah', 'Laporan PKL', 'Makalah Ilmiah', 'Skripsi', 'Presentasi PPT', 'Website Custom', 'KKN Report', 'Proposal Bisnis', 'Mind Mapping', 'Infografis'], ['Tugas Kuliah', 'Laporan PKL', 'Makalah Ilmiah', 'Skripsi', 'Presentasi PPT', 'Website Custom', 'KKN Report', 'Proposal Bisnis', 'Mind Mapping', 'Infografis']) as $item)
                <span class="flex items-center gap-3 text-navy-dark font-bold text-xs sm:text-sm uppercase tracking-wider">
                    <span>{{ $item }}</span>
                    <span class="text-navy opacity-40">◆</span>
                </span>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== STATS COUNTER ===================== --}}
<section class="relative py-14 sm:py-16 bg-cream overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8"
             x-data="counterSection()"
             x-intersect.once="startCounters()">
            @php
                $statsData = [
                    ['value' => (int) $stats['total_orders'], 'suffix' => '+', 'label' => 'Order Selesai',  'icon' => 'fas fa-check-circle', 'color' => 'text-navy',       'bg' => 'bg-navy/10'],
                    ['value' => (int) $stats['happy_clients'],'suffix' => '+', 'label' => 'Klien Puas',     'icon' => 'fas fa-heart',         'color' => 'text-red-500',    'bg' => 'bg-red-50'],
                    ['value' => (int) $stats['subjects'],     'suffix' => '+', 'label' => 'Mata Kuliah',    'icon' => 'fas fa-book',          'color' => 'text-gold',       'bg' => 'bg-gold/10'],
                    ['value' => $stats['rating'],             'suffix' => '★', 'label' => 'Rating',         'icon' => 'fas fa-star',          'color' => 'text-gold',       'bg' => 'bg-gold/10'],
                ];
            @endphp

            @foreach($statsData as $i => $stat)
                <div class="text-center" data-aos="fade-up" data-aos-delay="{{ $i * 80 }}">
                    <div class="inline-flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 rounded-2xl {{ $stat['bg'] }} mb-3 sm:mb-4">
                        <i class="{{ $stat['icon'] }} text-lg sm:text-xl {{ $stat['color'] }}"></i>
                    </div>
                    <div class="text-3xl sm:text-4xl font-extrabold font-display text-navy-dark mb-1">
                        @if(is_numeric($stat['value']))
                            <span x-text="counters[{{ $i }}]" data-target="{{ $stat['value'] }}">0</span>{{ $stat['suffix'] }}
                        @else
                            {{ $stat['value'] }}{{ $stat['suffix'] }}
                        @endif
                    </div>
                    <p class="text-gray-500 text-xs sm:text-sm font-medium">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
    {{-- Diagonal cut → white --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 56" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 56 L0 40 L1440 0 L1440 56 Z" fill="#ffffff"/>
        </svg>
    </div>
</section>

{{-- ===================== LAYANAN ===================== --}}
<section id="layanan" class="relative py-16 sm:py-20 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 sm:mb-14" data-aos="fade-up">
            <span class="text-gold font-bold text-xs sm:text-sm uppercase tracking-widest">Apa yang kami kerjain</span>
            <h2 class="font-display text-2xl sm:text-3xl md:text-4xl font-extrabold text-navy-dark mt-2">
                Layanan <span class="font-script text-gold">Lengkap</span> Buat Kamu
            </h2>
            <p class="text-gray-500 mt-3 max-w-xl mx-auto text-sm sm:text-base">Semua kebutuhan akademik dan digital kamu kami tangani dengan profesional.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
            @foreach($services as $i => $service)
                <div class="group bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                     data-aos="fade-up" data-aos-delay="{{ ($i % 3) * 80 }}">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-cream flex items-center justify-center mb-4 sm:mb-5 group-hover:bg-navy/10 transition-colors">
                        <i class="{{ $service->icon_class }} text-xl sm:text-2xl text-navy group-hover:text-gold transition-colors"></i>
                    </div>
                    <h3 class="font-display text-base sm:text-lg font-bold text-navy-dark mb-2">{{ $service->name }}</h3>
                    <p class="text-gray-500 text-xs sm:text-sm leading-relaxed mb-4 sm:mb-5">{{ $service->description }}</p>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400">Mulai dari</span>
                            <div class="text-navy font-extrabold font-display text-base sm:text-lg">
                                Rp {{ number_format($service->base_price, 0, ',', '.') }}
                            </div>
                        </div>
                        <a href="{{ route('order.create') }}?service={{ $service->id }}"
                           class="inline-flex items-center gap-1.5 bg-navy text-white text-xs font-bold px-3.5 py-2 rounded-xl hover:bg-navy-light transition-colors group-hover:bg-gold group-hover:text-navy-dark">
                            Order <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    {{-- Single arc → navy-dark --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 60 L0 40 Q720 0 1440 40 L1440 60 Z" fill="#0D1F3C"/>
        </svg>
    </div>
</section>

{{-- ===================== CARA KERJA ===================== --}}
<section id="cara-kerja" class="relative py-16 sm:py-20 bg-navy-dark overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 sm:mb-14" data-aos="fade-up">
            <span class="text-gold font-bold text-xs sm:text-sm uppercase tracking-widest">Mudah & Simpel</span>
            <h2 class="font-display text-2xl sm:text-3xl md:text-4xl font-extrabold text-white mt-2">
                Cara Kerja <span class="font-script text-gold">NativeCuy</span>
            </h2>
        </div>

        @php
            $steps = [
                ['num' => '01', 'icon' => 'fas fa-mouse-pointer', 'title' => 'Pilih Layanan',    'desc' => 'Pilih jenis tugas dari form order kami.'],
                ['num' => '02', 'icon' => 'fas fa-file-alt',      'title' => 'Isi Detail Tugas', 'desc' => 'Kasih info lengkap: judul, deskripsi, deadline, file referensi.'],
                ['num' => '03', 'icon' => 'fas fa-user-clock',    'title' => 'Kami Kerjakan',    'desc' => 'Expert langsung garap tugasmu dengan teliti.'],
                ['num' => '04', 'icon' => 'fas fa-check-double',  'title' => 'Terima Hasilnya',  'desc' => 'Sebelum deadline, revisi gratis jika ada yang kurang!'],
            ];
        @endphp

        {{-- Mobile: vertical stack --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 relative">
            {{-- Connector line (desktop only) --}}
            <div class="hidden lg:block absolute top-8 left-[12.5%] right-[12.5%] h-0.5 bg-navy/60" style="z-index:0"></div>

            @foreach($steps as $i => $step)
                <div class="text-center relative" style="z-index:1" data-aos="fade-up" data-aos-delay="{{ $i * 100 }}">
                    {{-- Step circle --}}
                    <div class="relative inline-flex flex-col items-center mb-4">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-navy border-2 border-gold/30 flex items-center justify-center hover:border-gold transition-colors">
                            <i class="{{ $step['icon'] }} text-gold text-lg sm:text-xl"></i>
                        </div>
                        <span class="absolute -top-3 -right-3 w-7 h-7 bg-gold rounded-full flex items-center justify-center text-navy-dark text-xs font-extrabold shadow-lg">
                            {{ $step['num'] }}
                        </span>
                    </div>
                    <h3 class="font-display font-bold text-white text-sm sm:text-base mb-2">{{ $step['title'] }}</h3>
                    <p class="text-gray-400 text-xs sm:text-sm leading-relaxed">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-10 sm:mt-12" data-aos="fade-up">
            <a href="{{ route('order.create') }}"
               class="btn-gold inline-flex items-center gap-2 px-7 sm:px-8 py-3.5 sm:py-4 rounded-full text-navy-dark font-bold text-sm sm:text-base">
                <i class="fas fa-rocket"></i> Mulai Order Sekarang
            </a>
        </div>
    </div>
    {{-- Diagonal reverse → cream --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 56" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 0 L1440 40 L1440 56 L0 56 Z" fill="#FDF8F0"/>
        </svg>
    </div>
</section>

{{-- ===================== KEUNGGULAN ===================== --}}
<section class="relative py-16 sm:py-20 bg-cream overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <div data-aos="fade-right">
                <span class="text-gold font-bold text-xs sm:text-sm uppercase tracking-widest">Kenapa NativeCuy?</span>
                <h2 class="font-display text-2xl sm:text-3xl md:text-4xl font-extrabold text-navy-dark mt-2 mb-5 sm:mb-6">
                    Bukan Sekadar Joki,<br>
                    Tapi Partner <span class="font-script text-gold">Akademik</span> Kamu
                </h2>
                <p class="text-gray-500 leading-relaxed mb-6 sm:mb-8 text-sm sm:text-base">
                    Kami paham tekanan deadline dan standar kampus. Nggak cuma ngerjain, tapi juga mastiin hasilnya worth it buat kamu.
                </p>
                <a href="https://wa.me/{{ \App\Models\Setting::get('wa_number', '6281234567890') }}?text=Halo%20NativeCuy"
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-navy text-white px-5 sm:px-6 py-3 rounded-xl font-bold hover:bg-navy-light transition-colors text-sm sm:text-base">
                    <i class="fab fa-whatsapp text-emerald-400"></i>
                    Chat Sekarang
                </a>
            </div>

            <div class="space-y-4 sm:space-y-5" data-aos="fade-left" data-aos-delay="100">
                @php
                    $features = [
                        ['icon' => 'fas fa-bolt',      'color' => 'bg-amber-50 text-amber-600',   'title' => 'Pengerjaan Cepat',   'desc' => 'Express 24 jam tersedia. Deadline mendesak? Kami tetap siap garap.'],
                        ['icon' => 'fas fa-shield-alt','color' => 'bg-emerald-50 text-emerald-600','title' => 'Anti Plagiarisme',   'desc' => 'Semua karya kami tulis original. Lolos turnitin & plagiarism checker.'],
                        ['icon' => 'fas fa-sync-alt',  'color' => 'bg-blue-50 text-blue-600',     'title' => 'Revisi Gratis',      'desc' => 'Tidak sesuai harapan? Revisi sampai puas tanpa biaya tambahan.'],
                        ['icon' => 'fas fa-lock',      'color' => 'bg-purple-50 text-purple-600', 'title' => 'Identitas Aman',     'desc' => 'Data dan identitas kamu kami jaga 100% kerahasiaannya.'],
                        ['icon' => 'fas fa-headset',   'color' => 'bg-rose-50 text-rose-600',     'title' => 'Konsultasi 24/7',    'desc' => 'Tim siap membantu kapanpun, termasuk tengah malam.'],
                    ];
                @endphp
                @foreach($features as $i => $f)
                    <div class="flex items-start gap-4 p-4 bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow"
                         data-aos="fade-left" data-aos-delay="{{ 100 + $i * 70 }}">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl {{ $f['color'] }} flex items-center justify-center flex-shrink-0">
                            <i class="{{ $f['icon'] }} text-sm sm:text-base"></i>
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

{{-- ===================== TESTIMONI ===================== --}}
<section id="testimoni" class="relative py-16 sm:py-20 bg-navy overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 sm:mb-14" data-aos="fade-up">
            <span class="text-gold font-bold text-xs sm:text-sm uppercase tracking-widest">Kata Mereka</span>
            <h2 class="font-display text-2xl sm:text-3xl md:text-4xl font-extrabold text-white mt-2">
                Sudah <span class="font-script text-gold">Ribuan</span> yang Puas
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
            @foreach($testimonials as $i => $t)
                <div class="bg-navy-dark rounded-2xl p-5 sm:p-6 border border-navy-light/30 hover:border-gold/30 transition-colors"
                     data-aos="fade-up" data-aos-delay="{{ ($i % 3) * 80 }}">
                    {{-- Service badge --}}
                    @if($t->service)
                        <div class="inline-flex items-center gap-1.5 bg-navy/50 border border-navy-light/40 rounded-full px-2.5 py-1 mb-4">
                            <span class="text-gold text-xs font-semibold">{{ $t->service->name }}</span>
                        </div>
                    @endif
                    {{-- Stars --}}
                    <div class="flex items-center gap-1 mb-3">
                        @for($s = 1; $s <= 5; $s++)
                            <i class="fas fa-star text-xs {{ $s <= $t->rating ? 'text-gold' : 'text-gray-700' }}"></i>
                        @endfor
                    </div>
                    {{-- Quote --}}
                    <p class="text-gray-300 text-xs sm:text-sm leading-relaxed mb-5 italic">"{{ $t->comment }}"</p>
                    {{-- Author --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 bg-navy-light rounded-full flex items-center justify-center text-gold font-bold text-xs sm:text-sm flex-shrink-0">
                            {{ $t->avatar_initials ?? strtoupper(substr($t->client_name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="text-white font-bold text-xs sm:text-sm">{{ $t->client_name }}</div>
                            <div class="text-gray-500 text-xs">Mahasiswa</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    {{-- Diagonal → cream --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 56" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 40 L1440 0 L1440 56 L0 56 Z" fill="#FDF8F0"/>
        </svg>
    </div>
</section>

{{-- ===================== FAQ ===================== --}}
<section id="faq" class="py-16 sm:py-20 bg-cream">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-10 sm:mb-12" data-aos="fade-up">
            <span class="text-gold font-bold text-xs sm:text-sm uppercase tracking-widest">Ada Pertanyaan?</span>
            <h2 class="font-display text-2xl sm:text-3xl md:text-4xl font-extrabold text-navy-dark mt-2">FAQ</h2>
        </div>

        @php
            $faqs = [
                ['q' => 'Apakah hasil kerja 100% original?',
                 'a' => 'Ya! Semua tugas kami kerjakan dari awal, bukan copy-paste. Hasilnya original dan siap melewati plagiarism checker.'],
                ['q' => 'Berapa lama waktu pengerjaan?',
                 'a' => 'Tergantung jenis dan kompleksitas tugas. Rata-rata 1–3 hari kerja. Ada layanan express 24 jam untuk deadline mendesak.'],
                ['q' => 'Apakah bisa revisi?',
                 'a' => 'Tentu! Revisi gratis sampai kamu puas, selama masih dalam ruang lingkup brief awal.'],
                ['q' => 'Bagaimana cara pembayaran?',
                 'a' => 'Bisa transfer bank (BCA, Mandiri, BNI), e-wallet (GoPay, OVO, Dana, ShopeePay), atau QRIS. DP 50% di awal.'],
                ['q' => 'Apakah identitas saya aman?',
                 'a' => 'Sangat aman. Kami tidak pernah menyebarkan informasi klien kepada siapapun. Privasi adalah prioritas kami.'],
                ['q' => 'Bagaimana cara tracking order?',
                 'a' => 'Setelah order masuk, kamu dapat kode tracking unik. Masukkan kode itu di halaman Tracking untuk lihat status real-time.'],
            ];
        @endphp

        <div class="space-y-3" x-data="{ open: null }">
            @foreach($faqs as $i => $faq)
                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm"
                     data-aos="fade-up" data-aos-delay="{{ $i * 50 }}">
                    <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                            class="w-full flex items-center justify-between px-5 sm:px-6 py-4 text-left gap-4">
                        <span class="font-display font-semibold text-navy-dark text-xs sm:text-sm">{{ $faq['q'] }}</span>
                        <i class="fas flex-shrink-0 transition-transform duration-200 text-gold text-sm"
                           :class="open === {{ $i }} ? 'fa-minus' : 'fa-plus'"></i>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse class="px-5 sm:px-6 pb-4">
                        <p class="text-gray-500 text-xs sm:text-sm leading-relaxed">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

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
                    <a href="https://wa.me/{{ \App\Models\Setting::get('wa_number', '6281234567890') }}" target="_blank"
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
            <path d="M0 60 L0 45 Q360 10 720 35 Q1080 60 1440 30 L1440 60 Z" fill="#030712"/>
        </svg>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Terminal mockup animation
    function terminalMockup() {
        const steps = [0, 20, 45, 70, 90, 100];
        return {
            activeStep: 0,
            progress: steps[0],
            start() {
                setInterval(() => {
                    this.activeStep = (this.activeStep + 1) % 5;
                    this.progress = steps[this.activeStep];
                }, 3000);
            }
        };
    }

    // Counter animation
    function counterSection() {
        return {
            counters: [0, 0, 0, 0],
            started: false,
            startCounters() {
                if (this.started) return;
                this.started = true;
                document.querySelectorAll('[data-target]').forEach((el, idx) => {
                    const target = parseInt(el.getAttribute('data-target'));
                    const duration = 1800;
                    const start = performance.now();
                    const update = (now) => {
                        const progress = Math.min((now - start) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        el.textContent = Math.floor(eased * target);
                        if (progress < 1) requestAnimationFrame(update);
                        else el.textContent = target;
                    };
                    requestAnimationFrame(update);
                });
            }
        };
    }
</script>
@endpush

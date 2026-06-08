<header class="fixed z-50 top-0 left-0 right-0 w-full"
        x-data="{ open: false, scrolled: false }"
        x-init="
            scrolled = window.scrollY > 40;
            window.addEventListener('scroll', () => scrolled = window.scrollY > 40, { passive: true });
        ">

    {{-- Outer wrapper: padding transition creates the pill "shrink" effect smoothly --}}
    <div class="transition-[padding] duration-500 ease-in-out"
         :class="scrolled ? 'px-3 sm:px-4 xl:px-[max(1rem,calc(50vw-512px))] pt-2.5' : 'px-0 pt-0'">

        {{-- Inner bar: transitions visual props only (no layout changes) --}}
        <div class="transition-[background-color,border-radius,box-shadow,border-color,backdrop-filter] duration-500 ease-in-out"
             :class="scrolled
                 ? 'rounded-2xl bg-white/96 backdrop-blur-xl shadow-lg shadow-black/5 border border-gray-200/70'
                 : 'bg-white/90 backdrop-blur-md border-b border-gray-100/80 rounded-none shadow-none'">

            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">

                    {{-- Logo --}}
                    <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0 group">
                        <img src="{{ asset('images/logo.png') }}" alt="NativeCuy"
                             class="h-11 w-auto transition-transform duration-200 group-hover:scale-105">
                    </a>

                    {{-- Desktop nav --}}
                    <nav class="hidden md:flex items-center gap-1">
                        @foreach([
                            ['href' => '#layanan',    'label' => 'Layanan'],
                            ['href' => '#cara-kerja', 'label' => 'Cara Kerja'],
                            ['href' => '#testimoni',  'label' => 'Testimoni'],
                            ['href' => '#faq',        'label' => 'FAQ'],
                        ] as $link)
                            <a href="{{ route('home') }}{{ $link['href'] }}"
                               class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-navy-dark hover:bg-gray-100/80 rounded-xl transition-all duration-150">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    {{-- Right: Tracking + CTA + Mobile toggle --}}
                    <div class="flex items-center gap-2 sm:gap-3">
                        <a href="{{ route('order.trackSearch') }}"
                           class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-navy-dark hover:bg-gray-100/80 px-3 py-2 rounded-xl transition-all duration-150">
                            <i class="fas fa-search text-xs"></i>
                            Tracking
                        </a>

                        <a href="{{ route('order.create') }}"
                           class="hidden md:inline-flex items-center gap-2 bg-gold text-navy-dark font-bold text-sm px-5 py-2.5 rounded-xl shadow-sm hover:shadow-md hover:shadow-gold/20 hover:scale-105 transition-all duration-200">
                            <i class="fas fa-bolt text-xs"></i>
                            Order Sekarang
                        </a>

                        <button @click="open = !open"
                                class="md:hidden p-2.5 rounded-xl text-gray-600 hover:bg-gray-100 transition-colors">
                            <i class="fas text-lg" :class="open ? 'fa-times' : 'fa-bars'"></i>
                        </button>
                    </div>
                </div>

                {{-- Mobile menu --}}
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="md:hidden py-3 border-t border-gray-100 space-y-0.5">
                    @foreach([
                        ['href' => '#layanan',    'label' => 'Layanan',    'icon' => 'fa-th-large'],
                        ['href' => '#cara-kerja', 'label' => 'Cara Kerja', 'icon' => 'fa-route'],
                        ['href' => '#testimoni',  'label' => 'Testimoni',  'icon' => 'fa-star'],
                        ['href' => '#faq',        'label' => 'FAQ',        'icon' => 'fa-question-circle'],
                    ] as $link)
                        <a href="{{ route('home') }}{{ $link['href'] }}"
                           @click="open = false"
                           class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-600 hover:text-navy-dark hover:bg-gray-100 rounded-xl transition-colors">
                            <i class="fas {{ $link['icon'] }} text-xs text-gray-400 w-4 text-center"></i>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                    <div class="pt-3 mt-1 border-t border-gray-100 space-y-2 px-1">
                        <a href="{{ route('order.trackSearch') }}" @click="open = false"
                           class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-gray-600 border-2 border-gray-200 hover:border-navy hover:text-navy transition-all">
                            <i class="fas fa-search text-xs"></i> Cek Tracking Order
                        </a>
                        <a href="{{ route('order.create') }}" @click="open = false"
                           class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-gold text-navy-dark text-sm font-bold shadow-sm">
                            <i class="fas fa-bolt text-xs"></i> Order Sekarang
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</header>

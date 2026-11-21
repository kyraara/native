{{-- ===================== TESTIMONI ===================== --}}
<section id="testimoni" class="relative py-16 sm:py-20 bg-navy overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 sm:mb-14" data-aos="fade-up">
            <h2 class="font-display text-2xl sm:text-3xl md:text-4xl font-extrabold text-white mt-2">
                Cerita Mereka, <span class="font-script text-gold">Bareng NativeCuy</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
            @forelse($testimonials as $i => $t)
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
                            <div class="text-gray-400 text-xs">Pelanggan NativeCuy</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-white py-7"><p>Belum ada ulasan yang ditampilkan.</p><a class="inline-flex mt-4 px-5 py-3 rounded-xl bg-gold font-semibold text-navy-dark" href="https://wa.me/{{ $publicContact['wa_number'] }}" target="_blank" rel="noopener noreferrer">Kenalan lewat konsultasi</a></div>
            @endforelse
        </div>
    </div>
    {{-- Diagonal → cream --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 56" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 40 L1440 0 L1440 56 L0 56 Z" fill="#FDF8F0"/>
        </svg>
    </div>
</section>

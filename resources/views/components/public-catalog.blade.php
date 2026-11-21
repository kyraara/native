{{-- ===================== LAYANAN ===================== --}}
<section id="layanan" x-data="publicCatalog()" class="relative py-16 sm:py-20 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 sm:mb-14" data-aos="fade-up">
            <h2 class="font-display text-2xl sm:text-3xl md:text-4xl font-extrabold text-navy-dark mt-2">
                Layanan <span class="font-script text-gold">Lengkap</span> Buat Kamu
            </h2>
            <p class="text-gray-500 mt-3 max-w-xl mx-auto text-sm sm:text-base">Semua kebutuhan akademik dan digital kamu kami tangani dengan profesional.</p>
        </div>

        <div class="legacy-catalog-filter"><label for="catalog-major">Cari layanan untuk jurusanmu</label><select id="catalog-major" x-model="major"><option value="">Semua jurusan</option>@foreach($majors as $major)<option value="{{ $major->id }}">{{ $major->name }}</option>@endforeach</select></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
            @forelse($services as $i => $service)
                <div x-show="matches(@js($service->majors->pluck('id')->all()))" class="legacy-service group bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                     data-aos="fade-up" data-aos-delay="{{ ($i % 3) * 80 }}">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-cream flex items-center justify-center mb-4 sm:mb-5 group-hover:bg-navy/10 transition-colors">
                        <x-service-icon :icon="$service->icon_class" class="legacy-service-symbol"/>
                    </div>
                    <h3 class="font-display text-base sm:text-lg font-bold text-navy-dark mb-2">{{ $service->name }}</h3>
                    <p class="text-gray-500 text-xs sm:text-sm leading-relaxed mb-4 sm:mb-5">{{ $service->description }}</p>
                    @php($priceList = $service->priceList())
                    @if($priceList)
                        <details class="catalog-prices">
                            <summary>Lihat daftar harga</summary>
                            @foreach($priceList as $group)
                                <div class="catalog-prices-group">
                                    <strong>{{ $group['label'] }}</strong>
                                    <ul>@foreach($group['items'] as $item)<li><span>{{ $item['label'] }}</span><b>{{ $item['price'] }}</b></li>@endforeach</ul>
                                </div>
                            @endforeach
                            <p>Harga mulai dari. Harga final disepakati saat konsultasi.</p>
                        </details>
                    @endif
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400">Mulai dari</span>
                            <div class="text-navy font-extrabold font-display text-base sm:text-lg">
                                Rp {{ number_format($service->display_price, 0, ',', '.') }}
                            </div>
                        </div>
                        <a href="{{ route('order.create', ['service' => $service->id]) }}" :href="@js(route('order.create', ['service' => $service->id])) + (major ? '&major=' + encodeURIComponent(major) : '')" aria-label="Order {{ $service->name }}"
                           class="inline-flex items-center gap-1.5 bg-navy text-white text-xs font-bold px-3.5 py-2 rounded-xl hover:bg-navy-light transition-colors group-hover:bg-gold group-hover:text-navy-dark">
                            Order <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center"><h3 class="font-bold text-navy">Katalog sedang disiapkan.</h3><p class="mt-2 text-gray-500">Konsultasikan kebutuhanmu langsung dengan admin.</p><a href="https://wa.me/{{ $publicContact['wa_number'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex mt-4 px-5 py-3 bg-navy text-white rounded-xl">Hubungi admin</a></div>
            @endforelse
        </div>
    </div>
    {{-- Single arc → navy-dark --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" class="w-full block">
            <path d="M0 60 L0 40 Q720 0 1440 40 L1440 60 Z" fill="#0D1F3C"/>
        </svg>
    </div>
</section>

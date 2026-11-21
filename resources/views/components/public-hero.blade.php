@php
    $heroServices = $services->map(fn($service) => [
        'id' => $service->id,
        'name' => $service->name,
        'price' => 'Rp '.number_format($service->display_price, 0, ',', '.'),
        'url' => route('order.create', ['service' => $service->id]),
    ])->values();
    $firstService = $heroServices->first();
@endphp
<section class="cuy-hero" aria-labelledby="home-heading" x-data="publicHero(@js($heroServices))" :class="{ 'is-ready': ready }">
    <div class="public-container">
        <div class="cuy-hero-layout">
            <div class="cuy-hero-copy">
                <h1 id="home-heading"><span class="cuy-hero-line"><span>Tugas numpuk?</span></span><span class="cuy-hero-line cuy-hero-script"><span>Beresin, Cuy.</span><svg viewBox="0 0 520 34" aria-hidden="true"><path d="M8 21C130 4 320 4 509 16M112 30C258 16 374 16 469 25"/></svg></span></h1>
                <p>Laporan, presentasi, sampai website. <br>Ceritakan yang kamu butuhkan. Kita bahas dan bereskan bareng.</p>
                <div class="cuy-hero-actions">
                    <a class="cuy-hero-primary" href="{{ route('order.create') }}">Mulai order <span><x-admin-icon name="arrow"/></span></a>
                    <a class="cuy-hero-consult" href="https://wa.me/{{ $publicContact['wa_number'] }}?text={{ urlencode('Halo NativeCuy, saya mau konsultasi kebutuhan saya.') }}" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i>Konsultasi dulu</a>
                </div>
                <p class="cuy-hero-agreement"><x-admin-icon name="check"/>Harga & deadline disepakati sebelum mulai.</p>
            </div>
            @if($firstService)
            <div class="cuy-service-picker" aria-label="Pilih layanan yang kamu butuhkan" @keydown.left.prevent="choose(active - 1)" @keydown.right.prevent="choose(active + 1)">
                <div class="cuy-service-top"><span>Mau dibantu apa?</span><a href="#layanan">Lihat semua <x-admin-icon name="arrow"/></a></div>
                <div class="cuy-service-stage" x-ref="stage" @pointermove="pointer($event)" @pointerleave="resetPointer()" @touchstart.passive="touchBegin($event)" @touchend.passive="touchEnd($event)" @touchcancel="touchStart = null">
                    <div class="cuy-service-orbit" aria-hidden="true"><svg viewBox="0 0 500 390"><ellipse cx="250" cy="195" rx="231" ry="155" transform="rotate(-24 250 195)"/><path d="M35 263C100 361 335 374 463 225"/><circle cx="463" cy="225" r="5"/></svg></div>
                    <div class="cuy-service-band" aria-hidden="true"></div>
                    <div class="cuy-service-slides">
                        @foreach($heroServices as $service)
                        @php($nameLength = mb_strlen($service['name']))
                        <div class="cuy-service-slide {{ $loop->first ? 'is-active' : '' }}" style="--name-size:{{ $nameLength > 65 ? 26 : ($nameLength > 35 ? 34 : 45) }}px;--name-mobile-size:{{ $nameLength > 65 ? 18 : ($nameLength > 35 ? 24 : 32) }}px" :class="{ 'is-active': active === {{ $loop->index }} }" :style="slideStyle({{ $loop->index }})" :aria-hidden="active !== {{ $loop->index }}" @if(!$loop->first) aria-hidden="true" @endif>
                            <h2>{{ $service['name'] }}</h2>
                        </div>
                        @endforeach
                    </div>
                    <span class="cuy-service-cross cuy-service-cross-first" aria-hidden="true"></span><span class="cuy-service-cross cuy-service-cross-last" aria-hidden="true"></span>
                </div>
                <div class="cuy-service-selection">
                    <p>Harga mulai<strong x-text="selected.price">{{ $firstService['price'] }}</strong></p>
                    <a href="{{ $firstService['url'] }}" :href="selected.url" :aria-label="'Pilih layanan ' + selected.name">Pilih layanan <x-admin-icon name="arrow"/></a>
                </div>
                @if($heroServices->count() > 1)
                <div class="cuy-service-controls" x-cloak><p><span x-text="String(active + 1).padStart(2, '0')">01</span><span>/ {{ str_pad($heroServices->count(), 2, '0', STR_PAD_LEFT) }}</span><small>Pilih yang kamu perlu</small></p><div><button type="button" @click="choose(active - 1)" aria-label="Layanan sebelumnya"><x-admin-icon name="arrow"/></button><button type="button" @click="choose(active + 1)" aria-label="Layanan berikutnya"><x-admin-icon name="arrow"/></button></div></div>
                @endif
                <span class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="selected.name + ', harga mulai ' + selected.price"></span>
            </div>
            @else
            <div class="cuy-hero-empty"><span class="cuy-empty-word" aria-hidden="true">Mulai dari<br>ceritamu.</span><p>Belum yakin layanan yang cocok? Konsultasikan kebutuhanmu dengan admin.</p><a href="https://wa.me/{{ $publicContact['wa_number'] }}" target="_blank" rel="noopener noreferrer">Yuk, ngobrol dulu <x-admin-icon name="arrow"/></a></div>
            @endif
        </div>
        <div class="cuy-hero-foot"><a href="#layanan"><span class="cuy-scroll-arrow"><x-admin-icon name="arrow"/></span>Temukan bantuanmu</a><a href="{{ route('order.trackSearch') }}">Sudah punya order? <strong>Cek progres <x-admin-icon name="arrow"/></strong></a></div>
    </div>
    <svg class="cuy-hero-wave" viewBox="0 0 1440 60" aria-hidden="true"><path d="M0 60L1440 60L1440 20C1200 55 900 0 600 18C300 36 0 0 0 20Z" fill="#F5B914"/></svg>
</section>

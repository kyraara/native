<header class="public-header public-header-overlay" x-data="publicNavigation" :class="{ 'is-scrolled': scrolled, 'is-menu-open': open }" @keydown.escape.window="close(true)" @click.outside="close()">
    <div class="public-nav-frame">
        <div class="public-nav-surface">
            <div class="public-nav-container">
                <nav class="public-nav" aria-label="Navigasi utama">
                    <a href="{{ route('home') }}" class="public-nav-logo" aria-label="NativeCuy, beranda"><img src="{{ asset('images/logo1.png') }}" alt="NativeCuy" width="85" height="54"></a>
                    <div class="public-nav-links">
                        @foreach(['layanan' => 'Layanan', 'cara-kerja' => 'Cara Kerja', 'testimoni' => 'Testimoni', 'faq' => 'FAQ'] as $id => $label)
                            <a href="{{ route('home') }}#{{ $id }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <div class="public-nav-actions">
                        <a class="public-nav-tracking" href="{{ route('order.trackSearch') }}"><x-admin-icon name="search"/>Tracking</a>
                        <a class="public-nav-order" href="{{ route('order.create') }}">Order sekarang <x-admin-icon name="arrow"/></a>
                        <button class="public-menu-toggle" type="button" x-ref="menuToggle" @click="open = !open" :aria-expanded="open" aria-controls="public-mobile-menu" :aria-label="open ? 'Tutup menu' : 'Buka menu'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path :d="open ? 'M6 6l12 12M6 18L18 6' : 'M4 7h16M4 12h16M4 17h16'"/></svg></button>
                    </div>
                </nav>
                <nav id="public-mobile-menu" class="public-mobile-menu" aria-label="Navigasi seluler" x-show="open" x-transition.opacity.duration.150ms x-cloak>
                    @foreach(['layanan' => 'Layanan', 'cara-kerja' => 'Cara Kerja', 'testimoni' => 'Testimoni', 'faq' => 'FAQ'] as $id => $label)
                        <a href="{{ route('home') }}#{{ $id }}" @click="close()">{{ $label }}</a>
                    @endforeach
                    <div class="public-mobile-actions">
                        <a class="public-nav-tracking" href="{{ route('order.trackSearch') }}"><x-admin-icon name="search"/>Cek tracking order</a>
                        <a class="public-nav-order" href="{{ route('order.create') }}">Order sekarang <x-admin-icon name="arrow"/></a>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</header>

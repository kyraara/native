<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') — NativeCuy</title>
    <link rel="icon" href="{{ asset('images/nativecuy_icon_32x32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('adminNotifications', {
                ...@js($orderNotifications),
                refreshing: false,
                async refresh() {
                    if (this.refreshing) return;
                    this.refreshing = true;
                    try {
                        const response = await fetch(@js(route('admin.notifications')), { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                        if (response.ok) {
                            const data = await response.json();
                            this.count = data.count;
                            this.items = data.items;
                        }
                    } catch (_) {
                        // Keep the last loaded notifications during a network interruption.
                    } finally {
                        this.refreshing = false;
                    }
                },
            });
            Alpine.store('adminToast', {
                show: false, message: '', type: 'success', timer: null,
                fire(message, type = 'success') {
                    window.clearTimeout(this.timer);
                    this.message = message;
                    this.type = type;
                    this.show = true;
                    this.timer = window.setTimeout(() => this.show = false, 3500);
                },
            });
        });
        window.showAdminToast = (message, type = 'success') => Alpine.store('adminToast').fire(message, type);
    </script>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
</head>
@php
    $pendingCount = $orderNotifications['count'];
    $badge = $pendingCount > 99 ? '99+' : $pendingCount;
    $navGroups = [
        'Operasional' => [
            ['route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard'), 'label' => 'Ringkasan', 'icon' => 'grid'],
            ['route' => 'admin.orders.index', 'active' => request()->routeIs('admin.orders.*'), 'label' => 'Order', 'icon' => 'orders', 'badge' => true],
            ['route' => 'admin.reports.revenue', 'active' => request()->routeIs('admin.reports.*'), 'label' => 'Penghasilan', 'icon' => 'wallet'],
        ],
        'Katalog' => [
            ['route' => 'admin.catalog.index', 'params' => ['tab' => 'services'], 'active' => request()->routeIs('admin.services.*') || (request()->routeIs('admin.catalog.*') && request('tab') !== 'majors'), 'label' => 'Layanan & harga', 'icon' => 'book'],
            ['route' => 'admin.catalog.index', 'params' => ['tab' => 'majors'], 'active' => request()->routeIs('admin.catalog.*') && request('tab') === 'majors', 'label' => 'Jurusan', 'icon' => 'cap'],
        ],
        'Pengaturan' => [
            ['route' => 'admin.settings.index', 'active' => request()->routeIs('admin.settings.index'), 'label' => 'Umum & kontak', 'icon' => 'settings'],
            ['route' => 'admin.settings.notifications', 'active' => request()->routeIs('admin.settings.notifications*'), 'label' => 'Notifikasi WhatsApp', 'icon' => 'bell'],
            ['route' => 'admin.testimonials.index', 'active' => request()->routeIs('admin.testimonials.*'), 'label' => 'Testimoni', 'icon' => 'quote'],
        ],
    ];
    $initial = mb_strtoupper(mb_substr(auth()->user()->name, 0, 1));
@endphp
<body class="nc-admin" x-data="adminWorkspace" @keydown.escape.window="closeMenu()">
<a class="nc-skip" href="#workspace">Langsung ke konten</a>

<div x-show="$store.adminToast.show" x-transition role="status" aria-live="polite"
     class="admin-toast fixed right-5 top-5 z-[80] flex max-w-sm items-start gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-2xl"
     :class="$store.adminToast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'" style="display:none">
    <span x-text="$store.adminToast.message"></span>
</div>

<button class="adm-scrim" x-show="mobile && menuOpen" x-cloak type="button" tabindex="-1" @click="closeMenu()" aria-label="Tutup menu admin"></button>

<aside id="admin-menu" class="adm-sidebar" x-ref="menu" :class="{ 'is-open': menuOpen }" :inert="mobile && !menuOpen" :aria-hidden="mobile && !menuOpen ? 'true' : null" :role="mobile ? 'dialog' : null" :aria-modal="mobile && menuOpen ? 'true' : null" aria-label="Menu admin NativeCuy" @keydown.tab="trapMenu($event)">
    <div class="adm-sidebar-head">
        <a class="adm-brand" href="{{ route('admin.dashboard') }}">
            <span class="adm-brand-mark"><img src="{{ asset('images/nativecuy_icon_64x64.png') }}" alt="" width="32" height="32"></span>
            <span><strong>Native<span class="brand-accent">Cuy</span></strong><small>ADMIN WORKSPACE</small></span>
        </a>
        <button type="button" class="adm-sidebar-close" x-ref="menuClose" @click="closeMenu()" aria-label="Tutup menu admin"><x-admin-icon name="close"/></button>
    </div>

    @foreach($navGroups as $group => $items)
        <div class="adm-nav-group">
            <span class="adm-nav-label">{{ $group }}</span>
            <nav class="adm-nav" aria-label="{{ $group }}">
                @foreach($items as $item)
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" @if($item['active']) aria-current="page" @endif>
                        <x-admin-icon :name="$item['icon']"/><span>{{ $item['label'] }}</span>
                        @if(! empty($item['badge']))
                            <span class="adm-badge" x-show="$store.adminNotifications.count > 0" x-text="$store.adminNotifications.count > 99 ? '99+' : $store.adminNotifications.count" aria-label="Order menunggu konfirmasi" @if(! $pendingCount) style="display:none" @endif>{{ $badge }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
    @endforeach

    <div class="adm-sidebar-foot">
        <a class="adm-site-link" href="{{ route('home') }}" target="_blank" rel="noopener">Lihat website <x-admin-icon name="external"/></a>
        <div class="adm-user">
            <span class="adm-user-avatar" aria-hidden="true">{{ $initial }}</span>
            <span class="adm-user-name">{{ auth()->user()->name }}<small>Administrator</small></span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" title="Keluar" aria-label="Keluar dari akun"><x-admin-icon name="logout"/></button></form>
        </div>
    </div>
</aside>

<div class="adm-main" :inert="mobile && menuOpen">
    <header class="adm-topbar" :class="{ 'is-search-open': searchOpen }">
        <button type="button" class="adm-icon-btn adm-menu-toggle" @click="openMenu()" :aria-expanded="menuOpen" aria-controls="admin-menu" aria-label="Buka menu admin"><x-admin-icon name="menu"/></button>
        <nav class="adm-crumbs" aria-label="Breadcrumb"><span>Workspace</span><x-admin-icon name="chevron"/><strong>@yield('toolbar-title', View::getSection('title', 'Ringkasan'))</strong></nav>

        <div class="adm-topbar-actions">
            <form id="admin-toolbar-search" class="adm-search" method="GET" action="{{ route('admin.orders.index') }}" role="search">
                <x-admin-icon name="search"/>
                <input type="search" name="search" x-ref="adminSearch" value="{{ is_scalar(request('search', '')) ? request('search', '') : '' }}" aria-label="Cari order berdasarkan judul, klien, atau token" placeholder="Cari order, klien, atau kode…">
                <button type="submit">Cari</button>
            </form>
            <button type="button" class="adm-icon-btn adm-search-toggle" @click="toggleSearch()" :aria-expanded="searchOpen" aria-controls="admin-toolbar-search" aria-label="Buka pencarian order"><x-admin-icon name="search"/></button>
            <a class="adm-new-order" href="{{ route('admin.orders.create') }}"><x-admin-icon name="plus"/><span>Order baru</span></a>

            <div class="adm-notify" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.stop.prevent="open = false; $refs.bell.focus()">
                <button type="button" class="adm-icon-btn" x-ref="bell" @click="open = !open; if (open) $store.adminNotifications.refresh()" :aria-expanded="open" aria-expanded="false" aria-controls="admin-notification-panel" aria-label="Notifikasi order">
                    <x-admin-icon name="bell"/>
                    <span class="adm-count" x-show="$store.adminNotifications.count > 0" x-text="$store.adminNotifications.count > 99 ? '99+' : $store.adminNotifications.count" @if(! $pendingCount) style="display:none" @endif>{{ $badge }}</span>
                </button>
                <section id="admin-notification-panel" class="adm-notify-panel" x-show="open" x-transition.opacity x-cloak aria-label="Order menunggu konfirmasi">
                    <div class="adm-notify-head"><div><strong>Notifikasi order</strong><p><span x-text="$store.adminNotifications.count"></span> order menunggu konfirmasi</p></div><button type="button" @click="open = false; $refs.bell.focus()" aria-label="Tutup notifikasi"><x-admin-icon name="close"/></button></div>
                    <div class="adm-notify-list">
                        <template x-for="item in $store.adminNotifications.items" :key="item.id">
                            <a :href="item.url" class="adm-notify-item"><span><x-admin-icon name="orders"/></span><span><strong x-text="item.title"></strong><small x-text="item.client"></small><time x-text="item.time"></time></span><x-admin-icon name="chevron"/></a>
                        </template>
                        <div class="adm-notify-empty" x-show="$store.adminNotifications.count === 0"><strong>Semua sudah ditinjau</strong>Belum ada order yang menunggu konfirmasi.</div>
                    </div>
                    <a class="adm-notify-all" href="{{ route('admin.orders.index', ['status' => 'pending']) }}">Lihat semua order menunggu <x-admin-icon name="arrow"/></a>
                </section>
            </div>
            <span class="adm-avatar" title="{{ auth()->user()->name }}" aria-hidden="true">{{ $initial }}</span>
        </div>
    </header>

    <main id="workspace" class="adm-content" tabindex="-1">
        <div class="adm-page-head">
            <div><h1>@yield('page-title', 'Ringkasan')</h1>@hasSection('page-subtitle')<p>@yield('page-subtitle')</p>@endif</div>
            @hasSection('page-actions')<div class="adm-page-actions">@yield('page-actions')</div>@endif
        </div>

        @foreach(['success' => 'success', 'error' => 'danger'] as $messageType => $tone)
            @if(session($messageType))
                <div role="status" class="ui-alert ui-alert-{{ $tone }} adm-flash">
                    <x-admin-icon :name="$messageType === 'success' ? 'check' : 'alert'"/>
                    <span>{{ session($messageType) }}</span>
                    <button type="button" onclick="this.parentElement.remove()" aria-label="Tutup pemberitahuan">×</button>
                </div>
            @endif
        @endforeach
        @if($errors->any())
            <div role="alert" class="ui-alert ui-alert-danger adm-flash">
                <x-admin-icon name="alert"/>
                <div><strong>Periksa kembali data berikut:</strong><ul style="margin-top:4px;padding-left:18px;list-style:disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif

        @yield('content')
    </main>
    <footer class="adm-footer"><span>© {{ date('Y') }} NativeCuy · Admin workspace</span><span>{{ today()->locale('id')->translatedFormat('l, d F Y') }}</span></footer>
</div>

<nav class="adm-bottom-nav" aria-label="Navigasi cepat admin" :inert="menuOpen">
    @foreach([['admin.dashboard', 'admin.dashboard', 'Ringkasan', 'grid'], ['admin.orders.index', 'admin.orders.*', 'Order', 'orders'], ['admin.reports.revenue', 'admin.reports.*', 'Penghasilan', 'wallet']] as [$route, $pattern, $label, $icon])
        <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif><x-admin-icon :name="$icon"/><span>{{ $label }}</span></a>
    @endforeach
    <button type="button" @click="openMenu()" :aria-expanded="menuOpen" aria-controls="admin-menu" @class(['is-current' => request()->routeIs('admin.catalog.*', 'admin.services.*', 'admin.settings.*', 'admin.testimonials.*')])><x-admin-icon name="menu"/><span>Lainnya</span></button>
</nav>
@stack('scripts')
</body>
</html>

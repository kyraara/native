<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Masuk') — NativeCuy Admin</title>
    <link rel="icon" href="{{ asset('images/nativecuy_icon_32x32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
</head>
<body class="nc-admin">
    <div class="auth">
        <aside class="auth-brand">
            <a class="auth-logo" href="{{ route('home') }}" aria-label="NativeCuy — kembali ke website">
                <span class="adm-brand-mark"><img src="{{ asset('images/nativecuy_icon_64x64.png') }}" alt="" width="38" height="38"></span>
                <span><strong>Native<span class="brand-accent">Cuy</span></strong><small>ADMIN WORKSPACE</small></span>
            </a>
            <div class="auth-pitch">
                <span class="auth-eyebrow">Ruang kerja tim</span>
                <h2>Semua order, harga, dan klien <span>dalam satu tempat.</span></h2>
                <p>Kelola order yang masuk, sepakati harga final, dan kabari klien lewat WhatsApp tanpa pindah aplikasi.</p>
                <ul class="auth-points">
                    <li><x-admin-icon name="orders"/>Pantau order dari website, WhatsApp, dan Instagram.</li>
                    <li><x-admin-icon name="wallet"/>Estimasi otomatis per layanan, harga final setelah konsultasi.</li>
                    <li><x-admin-icon name="chat"/>Notifikasi WhatsApp ke admin dan klien.</li>
                </ul>
            </div>
            <img class="auth-mascot" src="{{ asset('images/nativecuy_icon_256x256.png') }}" alt="" width="120" height="120">
            <p class="auth-brand-foot">© {{ date('Y') }} NativeCuy</p>
        </aside>

        <main class="auth-panel">
            <div class="auth-card">
                @yield('content')
                @hasSection('foot')
                    @yield('foot')
                @else
                    <a class="auth-back" href="{{ route('home') }}"><x-admin-icon name="arrow"/> Kembali ke website</a>
                @endif
            </div>
        </main>
    </div>
</body>
</html>

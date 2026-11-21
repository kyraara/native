<nav class="settings-tabs" aria-label="Pengaturan">
    <a href="{{ route('admin.settings.index') }}" @if(request()->routeIs('admin.settings.index')) aria-current="page" @endif><x-admin-icon name="settings"/>Umum & kontak</a>
    <a href="{{ route('admin.settings.notifications') }}" @if(request()->routeIs('admin.settings.notifications*')) aria-current="page" @endif><x-admin-icon name="bell"/>Notifikasi</a>
    <a href="{{ route('admin.testimonials.index') }}" @if(request()->routeIs('admin.testimonials.*')) aria-current="page" @endif><x-admin-icon name="quote"/>Testimoni</a>
</nav>

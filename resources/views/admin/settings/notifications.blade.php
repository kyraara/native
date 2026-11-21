@extends('layouts.admin')
@section('title', 'Notifikasi')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Atur pesan WhatsApp otomatis untuk admin dan klien.')

@section('content')
@include('admin.settings.nav')

<div class="ui-grid-main">
    <form method="POST" action="{{ route('admin.settings.notifications.update') }}" class="ui-stack">
        @csrf @method('PUT')

        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Notifikasi ke admin</h2><p>Dikirim ke nomor WhatsApp admin.</p></div></div>
            <div class="ui-card-body">
                @foreach($toggles->where('audience', 'admin') as $key => $toggle)
                    <label class="ntf-toggle"><input type="checkbox" name="{{ $key }}" value="1" @checked($toggle['enabled'])><span class="ntf-switch" aria-hidden="true"></span><span><strong>{{ $toggle['label'] }}</strong><small>{{ $toggle['help'] }}</small></span></label>
                @endforeach
                <div class="ui-field" style="margin-top:18px;max-width:340px">
                    <label class="ui-label" for="admin-target">Nomor WhatsApp admin</label>
                    <input id="admin-target" name="notify_admin_target" value="{{ old('notify_admin_target', $adminTarget) }}" class="ui-input" placeholder="{{ $envTarget ?: '6281234567890' }}" @error('notify_admin_target') aria-invalid="true" @enderror>
                    <p class="ui-help">Kosongkan untuk memakai nomor dari .env{{ $envTarget ? " ({$envTarget})" : '' }}.</p>
                    @error('notify_admin_target')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Notifikasi ke klien</h2><p>Dikirim ke nomor WhatsApp yang diisi klien saat order.</p></div></div>
            <div class="ui-card-body">
                @foreach($toggles->where('audience', 'client') as $key => $toggle)
                    <label class="ntf-toggle"><input type="checkbox" name="{{ $key }}" value="1" @checked($toggle['enabled'])><span class="ntf-switch" aria-hidden="true"></span><span><strong>{{ $toggle['label'] }}</strong><small>{{ $toggle['help'] }}</small></span></label>
                @endforeach
                <p class="ui-help" style="margin-top:12px">Saat mengubah status atau harga, admin tetap bisa memilih untuk tidak mengirim pesan pada order tertentu.</p>
            </div>
        </section>

        <div><button type="submit" class="ui-btn ui-btn-gold"><x-admin-icon name="check"/>Simpan pengaturan notifikasi</button></div>
    </form>

    <aside class="ui-stack">
        <section @class(['ui-card', 'ui-card-navy'])>
            <div class="ui-card-body">
                <span class="svc-eyebrow">STATUS WHATSAPP</span>
                <p class="ntf-state">{{ $ready ? 'Aktif' : 'Belum aktif' }}</p>
                <ul class="ntf-checks">
                    <li @class(['is-ok' => $switchedOn])>ORDER_WHATSAPP_NOTIFICATION_ENABLED {{ $switchedOn ? 'aktif' : 'belum true' }}</li>
                    <li @class(['is-ok' => $hasToken])>FONNTE_TOKEN {{ $hasToken ? 'terisi' : 'kosong' }}</li>
                </ul>
                <form method="POST" action="{{ route('admin.settings.notifications.test') }}" style="margin-top:14px">@csrf
                    <button class="ui-btn ui-btn-gold ui-btn-sm ui-btn-block" @disabled(! $ready)><x-admin-icon name="chat"/>Kirim pesan uji ke admin</button>
                </form>
            </div>
        </section>

        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Email order baru</h2><p>Dikirim ke {{ $email['recipient'] ?: 'alamat belum diatur' }}</p></div></div>
            <div class="ui-card-body">
                <ul class="ui-help" style="display:grid;gap:4px;margin-bottom:12px">
                    <li>Status: <strong>{{ $email['enabled'] ? 'aktif' : 'nonaktif (ORDER_EMAIL_NOTIFICATION_ENABLED)' }}</strong></li>
                    <li>Pengirim: {{ $email['mailer'] }}{{ $email['mailer'] === 'smtp' ? ' · '.$email['host'] : '' }}</li>
                    <li>Alamat pengirim: {{ $email['from'] ?: '-' }}</li>
                </ul>
                <form method="POST" action="{{ route('admin.settings.notifications.testEmail') }}">@csrf
                    <button class="ui-btn ui-btn-ghost ui-btn-sm ui-btn-block" @disabled(! $email['recipient'])><x-admin-icon name="mail"/>Kirim email uji</button>
                </form>
            </div>
        </section>

        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Antrean pengiriman</h2><p>Driver: {{ $queue['driver'] }}</p></div></div>
            <div class="ui-card-body">
                @if($queue['stuck'])
                    <div class="ui-alert ui-alert-warning"><x-admin-icon name="alert"/><div><strong>{{ $queue['waiting'] }} pesan tertahan.</strong> Worker antrean sepertinya tidak berjalan. Jalankan <code>php artisan queue:work</code> di server (atau lewat Supervisor/cron).</div></div>
                @elseif($queue['driver'] === 'database')
                    <p class="ui-help">{{ $queue['waiting'] }} pesan menunggu dikirim. Pastikan <code>php artisan queue:work</code> selalu berjalan agar pesan terkirim.</p>
                @else
                    <p class="ui-help">Pesan dikirim langsung saat order disimpan.</p>
                @endif
                @if($queue['failed'])
                    <p class="ui-error" style="margin-top:10px">{{ $queue['failed'] }} pengiriman gagal. Lihat dengan <code>php artisan queue:failed</code>.</p>
                @endif
            </div>
        </section>
    </aside>
</div>
@endsection

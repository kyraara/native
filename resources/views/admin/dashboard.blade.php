@extends('layouts.admin')

@php
    $hour = (int) now('Asia/Jakarta')->format('G');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
    $active = (int) (($counts['processing'] ?? 0) + ($counts['review'] ?? 0));
    $total = (int) $counts->sum();
    $maxDay = max(1, $chartDays->max('total'));
    $weekTotal = $chartDays->sum('total');
@endphp

@section('title', 'Ringkasan')
@section('page-title', $greeting.', '.\Illuminate\Support\Str::of(auth()->user()->name)->before(' '))
@section('page-subtitle', 'Ini yang perlu kamu perhatikan hari ini.')
@section('page-actions')
    <a href="{{ route('admin.orders.index') }}" class="ui-btn ui-btn-ghost"><x-admin-icon name="orders"/>Semua order</a>
@endsection

@section('content')
<section class="kpi-row" aria-label="Ringkasan angka">
    <a class="kpi is-primary" href="{{ route('admin.orders.index', ['status' => 'pending']) }}">
        <div class="kpi-top"><span class="kpi-label">Menunggu konfirmasi</span><span class="kpi-icon"><x-admin-icon name="clock"/></span></div>
        <strong class="kpi-value">{{ $counts['pending'] ?? 0 }}</strong>
        <span class="kpi-foot">{{ $actionTotals['needs_price'] }} belum ada harga final</span>
    </a>
    <a class="kpi" href="{{ route('admin.orders.index', ['status' => 'processing']) }}">
        <div class="kpi-top"><span class="kpi-label">Sedang berjalan</span><span class="kpi-icon"><x-admin-icon name="work"/></span></div>
        <strong class="kpi-value">{{ $active }}</strong>
        <span class="kpi-foot">{{ $counts['processing'] ?? 0 }} dikerjakan · {{ $counts['review'] ?? 0 }} review</span>
    </a>
    <a @class(['kpi', 'is-alert' => $actionTotals['overdue'] > 0]) href="{{ route('admin.orders.index') }}">
        <div class="kpi-top"><span class="kpi-label">Lewat deadline</span><span class="kpi-icon"><x-admin-icon name="alert"/></span></div>
        <strong class="kpi-value">{{ $actionTotals['overdue'] }}</strong>
        <span class="kpi-foot">{{ $actionTotals['overdue'] ? 'Segera kabari klien' : 'Semua sesuai jadwal' }}</span>
    </a>
    <a class="kpi" href="{{ route('admin.reports.revenue', ['month' => now('Asia/Jakarta')->format('Y-m')]) }}">
        <div class="kpi-top"><span class="kpi-label">Penghasilan {{ now('Asia/Jakarta')->locale('id')->translatedFormat('F') }}</span><span class="kpi-icon"><x-admin-icon name="wallet"/></span></div>
        <strong class="kpi-value">@rupiah($monthEarnings['revenue'])</strong>
        <span class="kpi-foot">{{ $monthEarnings['orders'] }} order selesai bulan ini</span>
    </a>
</section>

<div class="dash-grid">
    <section class="ui-card">
        <div class="ui-card-head"><div><h2>Perlu tindakan</h2><p>Order yang butuh harga final, lewat deadline, atau menunggu review.</p></div></div>
        @if($actions->isEmpty())
            <div class="ui-empty"><strong>Semua beres 🎉</strong>Tidak ada order yang menunggu tindakan.</div>
        @else
            <div class="dash-actions">
                @foreach($actions as $item)
                    @php($order = $item['order'])
                    <a class="dash-action" href="{{ route('admin.orders.show', $order) }}">
                        <span @class(['dash-action-dot', 'is-danger' => $item['tone'] === 'late', 'is-review' => $item['tone'] === 'review'])></span>
                        <div><strong>{{ $order->title }}</strong><small>{{ $order->client_name }} · {{ $order->service?->name ?? 'Layanan dihapus' }} · {{ $order->deadline->locale('id')->translatedFormat('d M') }}</small></div>
                        <span class="dash-tag is-{{ $item['tone'] }}">{{ $item['tag'] }}</span>
                        <x-admin-icon name="chevron"/>
                    </a>
                @endforeach
            </div>
        @endif
        <div class="ui-card-foot">
            <span>{{ $actionTotals['needs_price'] }} perlu harga · {{ $actionTotals['overdue'] }} terlambat · {{ $actionTotals['review'] }} review</span>
            <a class="ui-link" href="{{ route('admin.orders.index', ['status' => 'pending']) }}">Buka antrean <x-admin-icon name="arrow"/></a>
        </div>
    </section>

    <section class="ui-card">
        <div class="ui-card-head"><div><h2>Status order</h2><p>{{ $total }} order tercatat</p></div></div>
        <div class="ui-card-body">
            <div class="dash-pipeline-bar" role="img" aria-label="Komposisi status order">
                @foreach(\App\Models\Order::STATUSES as $key => $meta)
                    @if(($counts[$key] ?? 0) > 0)<span class="c-{{ $key }}" style="width: {{ round(($counts[$key] / max(1, $total)) * 100, 2) }}%"></span>@endif
                @endforeach
            </div>
            <div class="dash-legend">
                @foreach(\App\Models\Order::STATUSES as $key => $meta)
                    <a href="{{ route('admin.orders.index', ['status' => $key]) }}"><i class="c-{{ $key }}"></i>{{ $meta['label'] }}<b>{{ $counts[$key] ?? 0 }}</b></a>
                @endforeach
            </div>
        </div>
    </section>
</div>

<div class="dash-grid">
    <section class="ui-card">
        <div class="ui-card-head"><div><h2>Order masuk 7 hari terakhir</h2><p>{{ $weekTotal }} order baru</p></div></div>
        <div class="ui-card-body">
            <div class="dash-chart" role="img" aria-label="Grafik order masuk per hari">
                @foreach($chartDays as $day)
                    <div @class(['dash-bar', 'is-today' => $day['today']])>
                        <b>{{ $day['total'] }}</b>
                        <span class="dash-bar-fill" style="height: {{ max(3, round(($day['total'] / $maxDay) * 100)) }}%"></span>
                        <small>{{ $day['today'] ? 'Hari ini' : $day['label'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ui-card">
        <div class="ui-card-head"><div><h2>Tenggat terdekat</h2><p>Order aktif diurutkan dari deadline paling dekat.</p></div></div>
        @if($upcoming->isEmpty())
            <div class="ui-empty"><strong>Tidak ada order aktif</strong>Order baru akan muncul di sini.</div>
        @else
            <div class="dash-agenda">
                @foreach($upcoming as $order)
                    @php($late = $order->deadline->lt(today()))
                    <a href="{{ route('admin.orders.show', $order) }}">
                        <span @class(['dash-date', 'is-late' => $late])><b>{{ $order->deadline->format('d') }}</b><small>{{ $order->deadline->locale('id')->translatedFormat('M') }}</small></span>
                        <div><strong>{{ $order->title }}</strong><small>{{ $order->status_label }}{{ $late ? ' · lewat deadline' : '' }}</small></div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>

<div class="dash-grid">
    <section class="ui-card">
        <div class="ui-card-head"><div><h2>Aktivitas terbaru</h2><p>Perubahan order oleh admin, klien, dan sistem.</p></div><x-admin-icon name="history" style="color:var(--muted)"/></div>
        <div class="ui-card-body">
            @forelse($activities as $activity)
                <div class="ord-activity">
                    <span class="ord-activity-dot ord-activity-{{ $activity->action }}" aria-hidden="true"></span>
                    <div>
                        <p class="ord-activity-text"><a href="{{ route('admin.orders.show', $activity->order->tracking_token) }}">{{ $activity->order->title }}</a> — {{ $activity->description }}</p>
                        <p class="ui-help">{{ $activity->user?->name ?? ($activity->action === 'created' ? 'Klien' : 'Sistem') }} · {{ $activity->created_at->locale('id')->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="ui-help">Belum ada aktivitas. Riwayat akan muncul setelah ada order atau perubahan.</p>
            @endforelse
        </div>
    </section>

    <section class="ui-card ui-card-navy">
        <div class="ui-card-body">
            <div class="dash-catalog">
                <span class="dash-catalog-icon"><x-admin-icon name="book"/></span>
                <div>
                    <span class="svc-eyebrow">KATALOG</span>
                    <p style="font:700 16px 'Plus Jakarta Sans',sans-serif;margin-top:4px">{{ $stats['services'] }} layanan aktif · {{ $stats['majors'] }} jurusan</p>
                    <p class="ui-muted" style="font-size:12px">Atur field order dan harga tiap pilihan.</p>
                </div>
            </div>
            <div class="ui-row" style="margin-top:16px">
                <a href="{{ route('admin.catalog.index') }}" class="ui-btn ui-btn-gold ui-btn-sm">Kelola layanan</a>
                <a href="{{ route('admin.reports.revenue') }}" class="ui-btn ui-btn-sm" style="color:#fff;border-color:#ffffff33">Total penghasilan @rupiah($earnings['revenue'])</a>
            </div>
        </div>
    </section>
</div>
@endsection

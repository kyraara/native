@extends('layouts.admin')
@section('title', 'Penghasilan')
@section('page-title', 'Laporan penghasilan')
@section('page-subtitle', 'Rekap harga final dari order selesai, berdasarkan tanggal penyelesaian (WIB).')
@section('page-actions')
    <a class="ui-btn ui-btn-gold" href="{{ route('admin.reports.revenue.export', $month ? ['month' => $month->format('Y-m')] : []) }}"><x-admin-icon name="download"/>Unduh CSV</a>
@endsection

@section('content')
@php($periodLabel = $month ? $month->locale('id')->translatedFormat('F Y') : 'Semua periode')

<section class="ui-card" style="margin-bottom:20px">
    <div class="ui-card-body rev-filter">
        <div class="ui-spacer"><span class="ui-help">Periode laporan</span><p class="rev-period">{{ $periodLabel }}</p></div>
        <form method="GET" action="{{ route('admin.reports.revenue') }}" class="ui-row">
            <label class="sr-only" for="revenue-month">Pilih bulan laporan</label>
            <input id="revenue-month" type="month" name="month" value="{{ $month?->format('Y-m') }}" class="ui-input">
            <button type="submit" class="ui-btn ui-btn-primary">Tampilkan</button>
            @if($month)<a class="ui-btn ui-btn-ghost" href="{{ route('admin.reports.revenue') }}">Semua periode</a>@endif
        </form>
    </div>
</section>

<section class="kpi-row is-3" aria-label="Ringkasan penghasilan">
    <article class="kpi is-primary">
        <div class="kpi-top"><span class="kpi-label">Penghasilan {{ $month ? 'periode ini' : 'semua periode' }}</span><span class="kpi-icon"><x-admin-icon name="wallet"/></span></div>
        <strong class="kpi-value">@rupiah($summary['revenue'])</strong>
        <span class="kpi-foot">Jumlah harga final order berstatus Selesai</span>
    </article>
    <article class="kpi">
        <div class="kpi-top"><span class="kpi-label">Order selesai</span><span class="kpi-icon"><x-admin-icon name="check"/></span></div>
        <strong class="kpi-value">{{ number_format($summary['orders'], 0, ',', '.') }} <small>order</small></strong>
        <span class="kpi-foot">{{ $periodLabel }}</span>
    </article>
    <article @class(['kpi', 'is-alert' => $summary['unpriced'] > 0])>
        <div class="kpi-top"><span class="kpi-label">Harga final belum diisi</span><span class="kpi-icon"><x-admin-icon name="clock"/></span></div>
        <strong class="kpi-value">{{ number_format($summary['unpriced'], 0, ',', '.') }} <small>order</small></strong>
        <span class="kpi-foot">Belum menyumbang nilai penghasilan</span>
    </article>
</section>

<div class="ui-alert ui-alert-info" style="margin-bottom:16px"><x-admin-icon name="wallet"/><p>Penghasilan dihitung dari <strong>harga final order selesai</strong>. Order menunggu, dikerjakan, review, dan dibatalkan tidak dihitung. Angka ini bukan penanda pembayaran sudah diterima.</p></div>
@if($undated > 0)
    <div class="ui-alert ui-alert-warning" style="margin-bottom:16px"><x-admin-icon name="calendar"/><p><strong>{{ $undated }} order selesai lama</strong> belum memiliki tanggal selesai. Nilainya masuk total semua periode, tetapi belum masuk rekap bulanan.</p></div>
@endif

<section class="ui-card">
    <div class="ui-card-head"><div><h2>Rincian penghasilan</h2><p>{{ $orders->total() }} order selesai {{ $month ? 'pada periode ini' : 'di seluruh periode' }}</p></div></div>
    <div class="ui-table-wrap" tabindex="0" role="region" aria-label="Rincian penghasilan">
        <table class="ui-table orders-list">
            <thead><tr><th scope="col">Order</th><th scope="col">Layanan</th><th scope="col">Selesai (WIB)</th><th scope="col" class="ui-num">Harga final</th><th scope="col"><span class="sr-only">Detail</span></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td data-cell="order"><a class="ui-cell-title" href="{{ route('admin.orders.show', $order) }}">{{ $order->title }}</a><div class="ui-cell-sub"><span>{{ $order->client_name }}</span><span class="ui-mono">#{{ $order->tracking_token }}</span></div></td>
                        <td data-label="Layanan">{{ $order->service?->name ?? '—' }}</td>
                        <td data-label="Selesai (WIB)">@if($order->completed_at)<time datetime="{{ $order->completed_at->toIso8601String() }}">{{ $order->completed_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y') }}</time>@else<span class="ui-muted">Belum tercatat</span>@endif</td>
                        <td data-label="Harga final" class="ui-num orders-price">@if($order->price_final !== null)<strong>@rupiah($order->price_final)</strong>@else<a class="ui-link" href="{{ route('admin.orders.show', $order) }}">Isi harga final</a>@endif</td>
                        <td data-cell="action" class="ui-num"><a class="ui-btn ui-btn-ghost ui-btn-sm" href="{{ route('admin.orders.show', $order) }}" aria-label="Detail order {{ $order->title }}">Detail<x-admin-icon name="chevron"/></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="ui-empty"><strong>Belum ada order selesai{{ $month ? ' pada bulan ini' : '' }}</strong>Order akan muncul setelah ditandai Selesai.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())<div class="ui-card-foot">{{ $orders->onEachSide(1)->links('admin.partials.pagination') }}</div>@endif
    <div class="rev-total"><span>Total {{ $month ? 'periode ini' : 'semua periode' }}<small>{{ $summary['orders'] }} order, termasuk seluruh halaman</small></span><strong>@rupiah($summary['revenue'])</strong></div>
</section>
@if($month)<p class="ui-help" style="margin-top:12px">Total penghasilan semua periode: <strong>@rupiah($allTime['revenue'])</strong></p>@endif
@endsection

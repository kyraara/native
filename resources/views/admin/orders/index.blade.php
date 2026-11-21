@extends('layouts.admin')

@section('title', 'Kelola Order')
@section('page-title', 'Kelola Order')
@section('page-subtitle', 'Pantau order masuk, sepakati harga final, dan perbarui statusnya.')

@section('content')
@php
    $search = is_scalar(request('search')) ? (string) request('search') : '';
    $activeStatus = is_scalar(request('status')) ? (string) request('status') : '';
    $tabs = ['' => ['label' => 'Semua', 'count' => $statusCounts->sum()]]
        + collect($statuses)->map(fn ($meta, $key) => ['label' => $meta['label'], 'count' => (int) ($statusCounts[$key] ?? 0)])->all();
@endphp

<section class="ui-card">
    <form method="GET" class="orders-toolbar" role="search" aria-label="Cari order">
        @if($activeStatus !== '')<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
        @if($perPage !== $perPageOptions[0])<input type="hidden" name="per_page" value="{{ $perPage }}">@endif
        <input type="search" name="search" value="{{ $search }}" class="ui-input" aria-label="Cari token, nama klien, atau judul order" placeholder="Cari token, nama klien, atau judul…">
        <button type="submit" class="ui-btn ui-btn-primary"><x-admin-icon name="search"/>Cari</button>
        @if($search !== '' || $activeStatus !== '')
            <a href="{{ route('admin.orders.index') }}" class="ui-btn ui-btn-ghost">Reset</a>
        @endif
        <span class="ui-spacer"></span>
        <a href="{{ route('admin.orders.export', request()->only(['search', 'status'])) }}" class="ui-btn ui-btn-ghost"><x-admin-icon name="download"/>Export CSV</a>
    </form>

    <div class="ui-card-body" style="padding-bottom:14px">
        <nav class="orders-status-tabs" aria-label="Filter status order">
            @foreach($tabs as $key => $tab)
                <a href="{{ route('admin.orders.index', array_filter(['status' => $key, 'search' => $search, 'per_page' => $perPage !== $perPageOptions[0] ? $perPage : ''], fn ($value) => $value !== '')) }}" @if($activeStatus === (string) $key) aria-current="page" @endif>
                    {{ $tab['label'] }} <b>{{ $tab['count'] }}</b>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="ui-table-wrap" tabindex="0" role="region" aria-label="Daftar order">
        <table class="ui-table orders-list">
            <thead>
                <tr>
                    <th scope="col">Order</th>
                    <th scope="col">Klien</th>
                    <th scope="col">Deadline</th>
                    <th scope="col" class="ui-num">Harga</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php($isOverdue = $order->deadline->lt(today()) && ! in_array($order->status, ['done', 'cancelled'], true))
                    <tr x-data="inlineStatus({ currentStatus: @js($order->status), updateUrl: @js(route('admin.orders.updateStatus', $order)) })">
                        <td data-cell="order">
                            <a href="{{ route('admin.orders.show', $order) }}" class="ui-cell-title">{{ $order->title }}</a>
                            <div class="ui-cell-sub"><span class="ui-mono">#{{ $order->tracking_token }}</span><span aria-hidden="true">·</span><span>{{ $order->service?->name ?? 'Layanan dihapus' }}</span></div>
                        </td>
                        <td data-cell="client" data-label="Klien">
                            <div style="font-weight:600">{{ $order->client_name }}</div>
                            <div class="ui-cell-sub">{{ $order->client_phone }}</div>
                        </td>
                        <td data-cell="deadline" data-label="Deadline" @class(['orders-deadline', 'is-overdue' => $isOverdue])>
                            {{ $order->deadline->locale('id')->translatedFormat('d M Y') }}
                            <small>{{ $isOverdue ? 'Lewat deadline' : ($order->deadline->isToday() ? 'Hari ini' : $order->deadline->locale('id')->diffForHumans(today()->startOfDay(), \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW)) }}</small>
                        </td>
                        <td data-cell="price" data-label="Harga" class="ui-num orders-price">
                            @if($order->price_final !== null)
                                <strong>@rupiah($order->price_final)</strong><small>Harga final</small>
                            @elseif($order->price_estimate !== null)
                                <strong class="is-estimate">@rupiah($order->price_estimate)</strong><small>Estimasi · belum final</small>
                            @else
                                <strong class="ui-muted">—</strong><small>Belum ada harga</small>
                            @endif
                        </td>
                        <td data-cell="status" data-label="Status">
                            <select @change="updateStatus($event.target.value)" x-model="status" :disabled="saving" aria-label="Status order {{ $order->tracking_token }}"
                                    class="ui-status-select" :class="'ui-status-' + status">
                                @foreach($statuses as $key => $meta)
                                    <option value="{{ $key }}" @selected($order->status === $key)>{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td data-cell="action" class="ui-num">
                            <a href="{{ route('admin.orders.show', $order) }}" class="ui-btn ui-btn-ghost ui-btn-sm">Detail<x-admin-icon name="chevron"/></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="ui-empty"><strong>Tidak ada order ditemukan</strong>{{ $search !== '' || $activeStatus !== '' ? 'Coba ubah kata kunci atau filter status.' : 'Order baru akan muncul di sini.' }}</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="ui-card-foot orders-foot">
        <form method="GET" class="ui-row" style="gap:8px">
            @foreach(request()->only(['search', 'status']) as $name => $value)
                @if(is_scalar($value) && $value !== '')<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
            @endforeach
            <label for="per-page">Tampilkan</label>
            <select id="per-page" name="per_page" class="ui-select ui-select-sm" onchange="this.form.submit()">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <span>per halaman</span>
            <noscript><button class="ui-btn ui-btn-ghost ui-btn-sm">Terapkan</button></noscript>
        </form>
        <span>
            @if($orders->total())
                {{ $orders->firstItem() }}–{{ $orders->lastItem() }} dari {{ number_format($orders->total(), 0, ',', '.') }} order
            @else
                0 order
            @endif
        </span>
        {{ $orders->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</section>
@endsection

@push('scripts')
<script>
    function inlineStatus({ currentStatus, updateUrl }) {
        return {
            status: currentStatus,
            saving: false,
            async updateStatus(newStatus) {
                if (newStatus === currentStatus) return;
                this.saving = true;
                try {
                    const res = await fetch(updateUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ status: newStatus }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok) {
                        currentStatus = newStatus;
                        Alpine.store('adminToast').fire(data.message || 'Status diperbarui.', 'success');
                        Alpine.store('adminNotifications').refresh();
                    } else {
                        this.status = currentStatus;
                        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                        Alpine.store('adminToast').fire(firstError || data.message || 'Gagal memperbarui status.', 'error');
                    }
                } catch (e) {
                    this.status = currentStatus;
                    Alpine.store('adminToast').fire('Terjadi kesalahan jaringan.', 'error');
                } finally {
                    this.saving = false;
                }
            },
        };
    }
</script>
@endpush

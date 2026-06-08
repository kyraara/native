@extends('layouts.admin')

@section('title', 'Kelola Order')
@section('page-title', 'Kelola Order')
@section('page-subtitle', 'Manajemen semua order masuk')

@section('content')

{{-- Filter bar --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3" id="filterForm">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari token, nama klien, atau judul..."
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition">
        </div>
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition bg-white">
            <option value="">Semua Status</option>
            @foreach($statuses as $key => $val)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $val['label'] }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-navy text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-navy-light transition-colors whitespace-nowrap">
            <i class="fas fa-filter mr-1"></i> Filter
        </button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.orders.index') }}" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-500 hover:text-navy hover:border-navy transition-colors whitespace-nowrap">
                Reset
            </a>
        @endif
        <a href="{{ route('admin.orders.export', request()->only(['search', 'status'])) }}"
           class="inline-flex items-center gap-2 bg-green-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-green-700 transition-colors whitespace-nowrap">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Token</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Klien</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Layanan</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Judul</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Deadline</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50" id="ordersTable">
                @forelse($orders as $order)
                    @php
                        $isOverdue = $order->deadline->isPast() && !in_array($order->status, ['done', 'cancelled']);
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $isOverdue ? 'bg-red-50/40' : '' }}"
                        x-data="inlineStatus({
                            orderId: {{ $order->id }},
                            currentStatus: '{{ $order->status }}',
                            updateUrl: '{{ route('admin.orders.updateStatus', $order) }}',
                            csrfToken: '{{ csrf_token() }}'
                        })">
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-navy text-xs tracking-wider">{{ $order->tracking_token }}</span>
                            @if($isOverdue)
                                <span class="ml-1 text-red-500 text-xs" title="Lewat deadline"><i class="fas fa-exclamation-triangle"></i></span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-navy-dark">{{ $order->client_name }}</div>
                            <div class="text-gray-400 text-xs">{{ $order->client_email }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap text-xs">{{ $order->service?->name ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <div class="text-navy-dark font-medium max-w-xs truncate text-xs" title="{{ $order->title }}">{{ $order->title }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                            {{ $order->deadline->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4">
                            {{-- Inline quick status dropdown --}}
                            <div class="relative">
                                <select @change="updateStatus($event.target.value)"
                                        :disabled="saving"
                                        x-model="status"
                                        class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border-0 cursor-pointer focus:ring-2 focus:ring-navy/20 transition-all"
                                        :class="{
                                            'bg-yellow-100 text-yellow-700': status === 'pending',
                                            'bg-blue-100 text-blue-700': status === 'processing',
                                            'bg-purple-100 text-purple-700': status === 'review',
                                            'bg-green-100 text-green-700': status === 'done',
                                            'bg-red-100 text-red-700': status === 'cancelled',
                                            'opacity-60 cursor-wait': saving,
                                        }">
                                    @foreach($statuses as $key => $val)
                                        <option value="{{ $key }}">{{ $val['label'] }}</option>
                                    @endforeach
                                </select>
                                <span x-show="saving" class="absolute -right-5 top-1/2 -translate-y-1/2">
                                    <i class="fas fa-spinner fa-spin text-navy text-xs"></i>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="inline-flex items-center gap-1 bg-navy/10 text-navy text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-navy hover:text-white transition-colors">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                            <p class="font-medium">Tidak ada order ditemukan</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $orders->links() }}
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    function inlineStatus({ orderId, currentStatus, updateUrl, csrfToken }) {
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
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ status: newStatus }),
                    });

                    const data = await res.json();

                    if (res.ok) {
                        currentStatus = newStatus;
                        Alpine.store('adminToast').fire(data.message || 'Status diperbarui.', 'success');
                    } else {
                        this.status = currentStatus; // revert
                        Alpine.store('adminToast').fire(data.message || 'Gagal memperbarui status.', 'error');
                    }
                } catch (e) {
                    this.status = currentStatus;
                    Alpine.store('adminToast').fire('Terjadi kesalahan jaringan.', 'error');
                } finally {
                    this.saving = false;
                }
            }
        };
    }
</script>
@endpush

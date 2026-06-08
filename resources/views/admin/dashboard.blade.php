@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan aktivitas order NativeCuy')

@section('content')

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['label' => 'Total Order',         'value' => $stats['total'],      'icon' => 'fas fa-clipboard-list', 'iconBg' => 'bg-blue-100',   'iconColor' => 'text-blue-600',   'trend' => null],
            ['label' => 'Menunggu Konfirmasi', 'value' => $stats['pending'],    'icon' => 'fas fa-clock',          'iconBg' => 'bg-yellow-100', 'iconColor' => 'text-yellow-600', 'trend' => null, 'urgent' => $stats['pending'] > 0],
            ['label' => 'Sedang Dikerjakan',   'value' => $stats['processing'], 'icon' => 'fas fa-pen-nib',        'iconBg' => 'bg-purple-100', 'iconColor' => 'text-purple-600', 'trend' => null],
            ['label' => 'Selesai Bulan Ini',   'value' => $stats['done'],       'icon' => 'fas fa-check-double',   'iconBg' => 'bg-green-100',  'iconColor' => 'text-green-600',  'trend' => null],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="bg-white rounded-2xl p-5 shadow-sm border {{ isset($card['urgent']) && $card['urgent'] ? 'border-yellow-200 ring-1 ring-yellow-200' : 'border-gray-100' }}">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 {{ $card['iconBg'] }} rounded-xl flex items-center justify-center">
                    <i class="{{ $card['icon'] }} {{ $card['iconColor'] }} text-sm"></i>
                </div>
                @if(isset($card['urgent']) && $card['urgent'])
                    <span class="text-xs bg-yellow-100 text-yellow-700 font-bold px-2 py-0.5 rounded-full animate-pulse">Perlu aksi</span>
                @endif
            </div>
            <div class="text-3xl font-extrabold font-display text-navy-dark mb-0.5">{{ $card['value'] }}</div>
            <div class="text-gray-500 text-xs">{{ $card['label'] }}</div>
        </div>
    @endforeach
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Bar Chart: Order per 7 hari --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="font-display font-bold text-navy-dark text-sm">Order 7 Hari Terakhir</h2>
                <p class="text-gray-400 text-xs mt-0.5">Total order yang masuk per hari</p>
            </div>
            <span class="text-xs bg-navy/5 text-navy font-medium px-3 py-1.5 rounded-lg">
                Total: {{ $chartDays->sum('total') }}
            </span>
        </div>
        <div class="relative h-48">
            <canvas id="chartOrdersPerDay"></canvas>
        </div>
    </div>

    {{-- Doughnut: Order per layanan --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="mb-5">
            <h2 class="font-display font-bold text-navy-dark text-sm">Order per Layanan</h2>
            <p class="text-gray-400 text-xs mt-0.5">Breakdown jenis layanan</p>
        </div>
        <div class="relative h-40 mb-4">
            <canvas id="chartByService"></canvas>
        </div>
        {{-- Legend --}}
        <div class="space-y-1.5">
            @php
                $serviceColors = ['#1B3A6E','#F5B914','#3B82F6','#8B5CF6','#10B981','#EF4444'];
            @endphp
            @foreach($ordersByService as $i => $row)
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background: {{ $serviceColors[$i % count($serviceColors)] }}"></div>
                        <span class="text-gray-600 truncate max-w-28">{{ $row->service_name ?? 'Lainnya' }}</span>
                    </div>
                    <span class="font-bold text-navy-dark">{{ $row->total }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Recent Orders --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-display font-bold text-navy-dark">Order Terbaru</h2>
        <a href="{{ route('admin.orders.index') }}" class="text-xs text-navy hover:text-gold font-semibold transition-colors">
            Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Token</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Klien</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Layanan</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Deadline</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-semibold text-xs uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recentOrders as $order)
                    @php
                        $badgeClass = [
                            'pending'    => 'bg-yellow-100 text-yellow-700',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'review'     => 'bg-purple-100 text-purple-700',
                            'done'       => 'bg-green-100 text-green-700',
                            'cancelled'  => 'bg-red-100 text-red-700',
                        ][$order->status] ?? 'bg-gray-100 text-gray-600';
                        $isOverdue = $order->deadline->isPast() && !in_array($order->status, ['done','cancelled']);
                    @endphp
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="px-6 py-3.5">
                            <span class="font-mono font-bold text-navy text-xs tracking-wider">{{ $order->tracking_token }}</span>
                        </td>
                        <td class="px-6 py-3.5">
                            <div class="font-medium text-navy-dark text-sm">{{ $order->client_name }}</div>
                        </td>
                        <td class="px-6 py-3.5 text-gray-500 text-xs">{{ $order->service?->name ?? '—' }}</td>
                        <td class="px-6 py-3.5 text-xs {{ $isOverdue ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                            {{ $order->deadline->format('d M Y') }}
                        </td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $badgeClass }}">
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-navy hover:text-gold text-xs font-medium transition-colors">
                                Detail <i class="fas fa-chevron-right text-xs"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-3 block opacity-40"></i>
                            Belum ada order
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const chartColors = {
        navy:      '#1B3A6E',
        navyLight: '#2A4F96',
        gold:      '#F5B914',
        goldLight: 'rgba(245,185,20,0.15)',
    };

    // Bar Chart — Order per hari
    const daysData = @json($chartDays);
    new Chart(document.getElementById('chartOrdersPerDay'), {
        type: 'bar',
        data: {
            labels: daysData.map(d => d.label),
            datasets: [{
                label: 'Order Masuk',
                data: daysData.map(d => d.total),
                backgroundColor: daysData.map((d, i) =>
                    i === daysData.length - 1 ? chartColors.gold : 'rgba(27,58,110,0.15)'
                ),
                borderColor: daysData.map((d, i) =>
                    i === daysData.length - 1 ? chartColors.gold : chartColors.navy
                ),
                borderWidth: 1.5,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0D1F3C',
                    titleColor: '#F5B914',
                    bodyColor: '#e5e7eb',
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y} order`
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
                y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { stepSize: 1, font: { size: 11 }, color: '#9ca3af' }, beginAtZero: true }
            }
        }
    });

    // Doughnut Chart — Per layanan
    const serviceData = @json($ordersByService);
    const serviceColors = ['#1B3A6E','#F5B914','#3B82F6','#8B5CF6','#10B981','#EF4444'];
    new Chart(document.getElementById('chartByService'), {
        type: 'doughnut',
        data: {
            labels: serviceData.map(d => d.service_name || 'Lainnya'),
            datasets: [{
                data: serviceData.map(d => d.total),
                backgroundColor: serviceColors,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0D1F3C',
                    titleColor: '#F5B914',
                    bodyColor: '#e5e7eb',
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed} order`
                    }
                }
            }
        }
    });
</script>
@endpush

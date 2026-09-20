@extends('layouts.admin')
@section('title', 'Overview')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach([
        ['Gross revenue', '$' . number_format($stats['gross_revenue'], 2), 'emerald', '💰'],
        ['Platform earnings', '$' . number_format($stats['platform_earnings'], 2), 'indigo', '📈'],
        ['Paid orders', $stats['total_orders'], 'sky', '🧾'],
        ['Live products', $stats['live_products'], 'violet', '📦'],
        ['Total users', $stats['total_users'], 'slate', '👥'],
        ['Approved sellers', $stats['total_sellers'], 'amber', '🏪'],
        ['Products in queue', $stats['pending_products'], 'rose', '⏳'],
        ['Payout liability', '$' . number_format($stats['payout_liability'], 2), 'orange', '🏦'],
    ] as [$label, $value, $color, $emoji])
        <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="w-10 h-10 rounded-xl bg-{{ $color }}-100 flex items-center justify-center text-lg">
                    {{ $emoji }}
                </span>
            </div>
            <p class="text-2xl font-bold text-slate-900">{{ $value }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold">Revenue & orders — last 30 days</h2>
        <div class="flex items-center gap-4 text-xs text-slate-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> Revenue</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-violet-200"></span> Orders</span>
        </div>
    </div>
    <div class="h-72">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h2 class="font-bold mb-4">Payment methods</h2>
        @if($paymentMethodMix->isEmpty())
            <p class="text-sm text-slate-500">No paid orders yet.</p>
        @else
            <div class="h-64">
                <canvas id="paymentMethodChart"></canvas>
            </div>
        @endif
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h2 class="font-bold mb-4">Revenue by category</h2>
        @if($categoryRevenue->isEmpty())
            <p class="text-sm text-slate-500">No completed sales yet.</p>
        @else
            <div class="h-64">
                <canvas id="categoryChart"></canvas>
            </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @php
        $attention = [
            ['count' => $stats['pending_products'], 'route' => 'admin.products.pending', 'label' => 'Products awaiting review', 'emoji' => '📦'],
            ['count' => $stats['pending_sellers'], 'route' => 'admin.sellers.index', 'label' => 'Seller applications', 'emoji' => '🏪'],
            ['count' => $stats['pending_manual_payments'], 'route' => 'admin.manual-payments.index', 'label' => 'Manual payments to verify', 'emoji' => '🏦'],
            ['count' => $stats['pending_engagement'], 'route' => 'admin.engagement.index', 'label' => 'Free-unlock requests', 'emoji' => '🎬'],
            ['count' => $stats['pending_payouts'], 'route' => 'admin.payouts.index', 'label' => 'Payout requests', 'emoji' => '💸'],
        ];
        $hasAttention = collect($attention)->sum('count') > 0;
    @endphp

    @if($hasAttention)
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h2 class="font-bold mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            Needs your attention
        </h2>
        <div class="space-y-1">
            @foreach($attention as $item)
                @if($item['count'] > 0)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center justify-between py-2.5 px-3 -mx-3 rounded-lg hover:bg-slate-50 text-sm">
                        <span class="flex items-center gap-2">
                            <span>{{ $item['emoji'] }}</span>
                            {{ $item['label'] }}
                        </span>
                        <span class="bg-amber-100 text-amber-800 text-xs font-bold rounded-full min-w-[24px] h-6 flex items-center justify-center px-2">
                            {{ $item['count'] }}
                        </span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h2 class="font-bold mb-4">Recent orders</h2>
        @forelse($recentOrders as $order)
            <div class="flex justify-between items-center py-2.5 border-b border-slate-100 last:border-0 text-sm">
                <div>
                    <p class="font-medium">{{ $order->order_number }}</p>
                    <p class="text-xs text-slate-400">{{ $order->buyer->name }} &middot; {{ $order->created_at->diffForHumans() }}</p>
                </div>
                <span class="font-semibold">${{ number_format($order->grand_total, 2) }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">No orders yet.</p>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const revenueCanvas = document.getElementById('revenueChart');
    if (revenueCanvas) {
        const ctx = revenueCanvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(79, 70, 229, 0.25)');
        gradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

        new Chart(revenueCanvas, {
            data: {
                labels: @json($revenueTrend['labels']),
                datasets: [
                    {
                        type: 'line',
                        label: 'Revenue ($)',
                        data: @json($revenueTrend['revenue']),
                        borderColor: '#4f46e5',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#4f46e5',
                        borderWidth: 2.5,
                        yAxisID: 'y',
                    },
                    {
                        type: 'bar',
                        label: 'Orders',
                        data: @json($revenueTrend['orders']),
                        backgroundColor: 'rgba(196, 181, 253, 0.6)',
                        borderRadius: 4,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (item) {
                                return item.dataset.label === 'Revenue ($)'
                                    ? ' Revenue: $' + item.formattedValue
                                    : ' Orders: ' + item.formattedValue;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { callback: (v) => '$' + v },
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        grid: { display: false },
                        ticks: { precision: 0 },
                    },
                },
            },
        });
    }

    const paymentCanvas = document.getElementById('paymentMethodChart');
    if (paymentCanvas) {
        new Chart(paymentCanvas, {
            type: 'doughnut',
            data: {
                labels: @json($paymentMethodMix->keys()->map(fn($m) => ucfirst(str_replace('_', ' ', $m)))),
                datasets: [{
                    data: @json($paymentMethodMix->values()),
                    backgroundColor: ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#a855f7'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { size: 11 } } },
                    tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 8 },
                },
            },
        });
    }

    const categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas) {
        new Chart(categoryCanvas, {
            type: 'bar',
            data: {
                labels: @json($categoryRevenue->keys()),
                datasets: [{
                    label: 'Revenue',
                    data: @json($categoryRevenue->values()),
                    backgroundColor: '#818cf8',
                    borderRadius: 6,
                    barThickness: 18,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: { label: (item) => ' $' + item.formattedValue },
                    },
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: (v) => '$' + v } },
                    y: { grid: { display: false } },
                },
            },
        });
    }
});
</script>
@endpush
@endsection

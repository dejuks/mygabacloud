@extends('layouts.seller')
@section('title', 'Analytics')

@section('content')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach([
        ['Total earnings', '$' . number_format($stats['total_revenue'], 2), '💰'],
        ['Total sales', $stats['total_sales'], '🧾'],
        ['Average sale', '$' . number_format($stats['average_sale'], 2), '📊'],
        ['Live products', $stats['live_products'], '📦'],
    ] as [$label, $value, $emoji])
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <span class="text-lg">{{ $emoji }}</span>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $value }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold">Your earnings — last 30 days</h2>
        <div class="flex items-center gap-4 text-xs text-slate-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-fuchsia-500"></span> Earnings</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-fuchsia-200"></span> Sales</span>
        </div>
    </div>
    @if($stats['total_sales'] === 0)
        <p class="text-sm text-slate-500 py-8 text-center">No sales yet — this fills in once you start selling.</p>
    @else
        <div class="h-72">
            <canvas id="sellerRevenueChart"></canvas>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h2 class="font-bold mb-4">Earnings by category</h2>
        @if($categoryRevenue->isEmpty())
            <p class="text-sm text-slate-500">No completed sales yet.</p>
        @else
            <div class="h-64">
                <canvas id="sellerCategoryChart"></canvas>
            </div>
        @endif
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6">
        <h2 class="font-bold mb-4">Top products by earnings</h2>
        @forelse($topProducts as $row)
            <div class="flex justify-between items-center py-2.5 border-b border-slate-100 last:border-0 text-sm">
                <div class="min-w-0">
                    <p class="truncate">{{ $row->product->title ?? 'Deleted product' }}</p>
                    <p class="text-xs text-slate-400">{{ $row->sale_count }} sale{{ $row->sale_count == 1 ? '' : 's' }}</p>
                </div>
                <span class="font-semibold shrink-0 ml-3">${{ number_format($row->revenue, 2) }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">No sales yet.</p>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const revenueCanvas = document.getElementById('sellerRevenueChart');
    if (revenueCanvas) {
        const ctx = revenueCanvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(192, 38, 211, 0.25)');
        gradient.addColorStop(1, 'rgba(192, 38, 211, 0.0)');

        new Chart(revenueCanvas, {
            data: {
                labels: @json($revenueTrend['labels']),
                datasets: [
                    {
                        type: 'line',
                        label: 'Earnings ($)',
                        data: @json($revenueTrend['revenue']),
                        borderColor: '#c026d3',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#c026d3',
                        borderWidth: 2.5,
                        yAxisID: 'y',
                    },
                    {
                        type: 'bar',
                        label: 'Sales',
                        data: @json($revenueTrend['sales']),
                        backgroundColor: 'rgba(240, 171, 252, 0.6)',
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
                                return item.dataset.label === 'Earnings ($)'
                                    ? ' Earnings: $' + item.formattedValue
                                    : ' Sales: ' + item.formattedValue;
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

    const categoryCanvas = document.getElementById('sellerCategoryChart');
    if (categoryCanvas) {
        new Chart(categoryCanvas, {
            type: 'doughnut',
            data: {
                labels: @json($categoryRevenue->keys()),
                datasets: [{
                    data: @json($categoryRevenue->values()),
                    backgroundColor: ['#c026d3', '#a21caf', '#e879f9', '#f0abfc', '#86198f', '#701a75'],
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
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: { label: (item) => ' ' + item.label + ': $' + item.formattedValue },
                    },
                },
            },
        });
    }
});
</script>
@endpush
@endsection

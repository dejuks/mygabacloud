@extends('layouts.seller')
@section('title', 'Seller dashboard')

@section('content')
<div class="flex items-center justify-end mb-6">
    <a href="{{ route('seller.products.create') }}"
       class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
        + New product
    </a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    @foreach([
        ['Available balance', '$' . number_format($wallet->available_balance, 2), 'text-green-600'],
        ['Pending balance', '$' . number_format($wallet->pending_balance, 2), 'text-amber-600'],
        ['Total earned', '$' . number_format($stats['total_earned'], 2), 'text-slate-900'],
        ['Total sales', $stats['total_sales'], 'text-slate-900'],
        ['Live products', $stats['published'], 'text-slate-900'],
    ] as [$label, $value, $color])
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-2xl font-bold mt-1 {{ $color }}">{{ $value }}</p>
        </div>
    @endforeach
</div>

@if($stats['pending'] > 0)
    <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
        You have {{ $stats['pending'] }} product(s) awaiting admin review.
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="font-bold mb-4">Recent sales</h2>
        @forelse($recentSales as $sale)
            <div class="flex justify-between items-center py-3 border-b border-slate-100 last:border-0">
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate">{{ $sale->product->title }}</p>
                    <p class="text-xs text-slate-400">
                        {{ $sale->created_at->diffForHumans() }} &middot; {{ ucfirst($sale->license_type) }}
                    </p>
                </div>
                <div class="text-right shrink-0 ml-3">
                    <p class="text-sm font-semibold text-green-600">+${{ number_format($sale->seller_earning, 2) }}</p>
                    <p class="text-xs text-slate-400">of ${{ number_format($sale->price, 2) }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No sales yet.</p>
        @endforelse
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="font-bold mb-4">Top products</h2>
        @forelse($topProducts as $product)
            <div class="flex justify-between items-center py-3 border-b border-slate-100 last:border-0">
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate">{{ $product->title }}</p>
                    <p class="text-xs text-slate-400">{{ ucfirst(str_replace('_',' ', $product->status)) }}</p>
                </div>
                <span class="text-sm shrink-0 ml-3">{{ $product->sales_count }} sales</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">No products yet.</p>
        @endforelse
    </div>
</div>
@endsection

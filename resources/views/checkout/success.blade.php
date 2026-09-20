@extends('layouts.app')
@section('title', 'Order complete')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-xl p-8 text-center mb-6">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-2">Thank you for your purchase</h1>
        <p class="text-slate-500 text-sm">Order {{ $order->order_number }} &middot; ${{ number_format($order->grand_total, 2) }}</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6">
        <h2 class="font-bold mb-4">Your items</h2>
        @foreach($order->items as $item)
            <div class="border-b border-slate-100 py-3 last:border-0">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-medium text-sm">{{ $item->product->title }}</p>
                        <p class="text-xs text-slate-400">{{ ucfirst($item->license_type) }} license</p>
                    </div>
                    <span class="text-sm">${{ number_format($item->price, 2) }}</span>
                </div>
                @if($item->license)
                    <div class="bg-slate-50 rounded-lg px-3 py-2 font-mono text-xs">
                        License key: <strong>{{ $item->license->license_key }}</strong>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <a href="{{ route('library.index') }}"
       class="block text-center bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
        Go to your library to download
    </a>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Your cart')

@section('content')
<h1 class="text-2xl font-bold mb-6">Your cart</h1>

@if($items->isEmpty())
    <div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
        <p class="text-slate-500 mb-4">Your cart is empty.</p>
        <a href="{{ route('products.index') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Browse products
        </a>
    </div>
@else
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-3">
        @foreach($items as $row)
            <div class="bg-white border border-slate-200 rounded-xl p-4 flex gap-4 items-center">
                <div class="w-24 h-16 bg-slate-100 rounded-lg overflow-hidden shrink-0">
                    @if($row['product']->thumbnail)
                        <img src="{{ Storage::url($row['product']->thumbnail) }}" class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('products.show', $row['product']) }}" class="font-semibold text-sm hover:text-indigo-600">
                        {{ $row['product']->title }}
                    </a>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ ucfirst($row['license_type']) }} License &middot;
                        by {{ $row['product']->seller->sellerProfile->store_name ?? $row['product']->seller->name }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="font-bold">${{ number_format($row['price'], 2) }}</div>
                    <form method="POST" action="{{ route('cart.remove', $row['product']) }}">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-600 hover:underline mt-1">Remove</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="lg:col-span-1">
        <div class="bg-white border border-slate-200 rounded-xl p-6 sticky top-20">
            <h2 class="font-bold mb-4">Order summary</h2>

            <div class="space-y-2 text-sm mb-4">
                <div class="flex justify-between">
                    <span class="text-slate-500">Subtotal</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>
                @if($discount > 0)
                <div class="flex justify-between text-green-600">
                    <span>Discount ({{ $coupon->code }})</span>
                    <span>-${{ number_format($discount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-lg border-t pt-2">
                    <span>Total</span>
                    <span>${{ number_format($total, 2) }}</span>
                </div>
            </div>

            @if($coupon)
                <form method="POST" action="{{ route('cart.coupon.remove') }}" class="mb-4">
                    @csrf @method('DELETE')
                    <button class="text-xs text-red-600 hover:underline">Remove coupon</button>
                </form>
            @else
                <form method="POST" action="{{ route('cart.coupon.apply') }}" class="flex gap-2 mb-4">
                    @csrf
                    <input type="text" name="code" placeholder="Coupon code"
                           class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <button class="border border-slate-300 px-3 py-2 rounded-lg text-sm hover:bg-slate-50">Apply</button>
                </form>
            @endif

            @auth
                <a href="{{ route('checkout.index') }}"
                   class="block text-center w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                    Proceed to checkout
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="block text-center w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                    Log in to check out
                </a>
            @endauth
        </div>

        @php $freeEligible = $items->filter(fn ($row) => $row['product']->allow_free_unlock); @endphp
        @if($freeEligible->isNotEmpty())
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mt-4">
                <p class="text-sm font-medium text-green-800 mb-2">🎬 Don't want to pay?</p>
                <p class="text-xs text-green-700 mb-3">
                    {{ $freeEligible->count() === 1 ? 'This item' : 'Some items in your cart' }}
                    can be unlocked for free by watching a video and engaging on YouTube instead.
                </p>
                <div class="space-y-1.5">
                    @foreach($freeEligible as $row)
                        <a href="{{ route('unlock.create', $row['product']) }}"
                           class="block text-xs bg-white border border-green-300 text-green-700 rounded-lg px-3 py-2 hover:bg-green-100">
                            Get "{{ Str::limit($row['product']->title, 30) }}" free instead &rarr;
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@endif
@endsection

@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
<h1 class="text-2xl font-bold mb-6">Checkout</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-bold mb-4">Payment method</h2>

            @if($driver === 'test')
                <div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
                    <strong>Test mode.</strong> No real payment will be taken. The order will be completed
                    immediately so you can test the full flow.
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.process') }}" class="space-y-3">
                @csrf

                @if($driver === 'test')
                    <label class="flex items-center gap-3 p-4 border-2 border-indigo-500 bg-indigo-50 rounded-lg cursor-pointer">
                        <input type="radio" name="payment_method" value="test" checked>
                        <span class="font-medium text-sm">Simulated payment (test mode)</span>
                    </label>
                @else
                    <label class="flex items-center gap-3 p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-indigo-400">
                        <input type="radio" name="payment_method" value="stripe" checked>
                        <span class="flex-1">
                            <span class="font-medium text-sm block">Credit / Debit Card</span>
                            <span class="text-xs text-slate-400">Visa, Mastercard, Amex — via Stripe, instant</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-3 p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-indigo-400">
                        <input type="radio" name="payment_method" value="paypal">
                        <span class="flex-1">
                            <span class="font-medium text-sm block">PayPal</span>
                            <span class="text-xs text-slate-400">Instant</span>
                        </span>
                    </label>

                    <div class="pt-2 pb-1">
                        <p class="text-xs font-semibold uppercase text-slate-400">Manual verification &mdash; reviewed within a few hours</p>
                    </div>

                    <label class="flex items-center gap-3 p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-indigo-400">
                        <input type="radio" name="payment_method" value="cbe">
                        <span class="flex-1">
                            <span class="font-medium text-sm block">CBE Bank Transfer</span>
                            <span class="text-xs text-slate-400">Commercial Bank of Ethiopia</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-3 p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-indigo-400">
                        <input type="radio" name="payment_method" value="telebirr">
                        <span class="flex-1">
                            <span class="font-medium text-sm block">Telebirr</span>
                            <span class="text-xs text-slate-400">Mobile money</span>
                        </span>
                    </label>
                    <label class="flex items-center gap-3 p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-indigo-400">
                        <input type="radio" name="payment_method" value="usdt_manual">
                        <span class="flex-1">
                            <span class="font-medium text-sm block">USDT (TRC20)</span>
                            <span class="text-xs text-slate-400">Send from any Tron-network wallet</span>
                        </span>
                    </label>
                @endif

                <button class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 mt-4">
                    Pay ${{ number_format($total, 2) }}
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-bold mb-4">Your order</h2>
            <div class="space-y-3 mb-4 text-sm">
                @foreach($items as $row)
                    <div class="flex justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate">{{ $row['product']->title }}</p>
                            <p class="text-xs text-slate-400">{{ ucfirst($row['license_type']) }} license</p>
                        </div>
                        <span class="shrink-0">${{ number_format($row['price'], 2) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="border-t pt-3 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>${{ number_format($subtotal, 2) }}</span></div>
                @if($discount > 0)
                    <div class="flex justify-between text-green-600"><span>Discount</span><span>-${{ number_format($discount, 2) }}</span></div>
                @endif
                <div class="flex justify-between font-bold text-lg"><span>Total</span><span>${{ number_format($total, 2) }}</span></div>
            </div>
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
@endsection

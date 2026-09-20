@extends('layouts.app')
@section('title', 'Awaiting review')

@section('content')
<div class="max-w-lg mx-auto text-center">
    <div class="bg-white border border-slate-200 rounded-xl p-8">
        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold mb-2">Payment under review</h1>
        <p class="text-sm text-slate-500 mb-6">
            Order {{ $order->order_number }} &middot; ${{ number_format($order->grand_total, 2) }}<br>
            We've received your proof of payment and will review it shortly. You'll get your download
            access as soon as it's approved — usually within a few hours.
        </p>
        <a href="{{ route('library.index') }}" class="text-indigo-600 text-sm hover:underline">
            Check your library later
        </a>
    </div>
</div>
@endsection

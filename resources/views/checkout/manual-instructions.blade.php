@extends('layouts.app')
@section('title', 'Complete your payment')

@section('content')
<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-1">{{ $instructions['title'] }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        Order {{ $order->order_number }} &middot; <strong>${{ number_format($order->grand_total, 2) }}</strong>
    </p>

    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6">
        <h2 class="font-semibold text-sm text-slate-500 uppercase tracking-wide mb-3">Payment details</h2>
        <dl class="space-y-3">
            @foreach($instructions['lines'] as $label => $value)
                @if($value)
                <div class="flex items-center justify-between gap-3" x-data="{ copied: false }">
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-400">{{ $label }}</dt>
                        <dd class="font-mono text-sm font-medium break-all">{{ $value }}</dd>
                    </div>
                    <button type="button" class="shrink-0 text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 hover:bg-slate-50"
                            @click="navigator.clipboard.writeText('{{ addslashes($value) }}'); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="text-green-600">Copied!</span>
                    </button>
                </div>
                @endif
            @endforeach
            <div class="flex items-center justify-between gap-3" x-data="{ copied: false }">
                <div>
                    <dt class="text-xs text-slate-400">Amount to send</dt>
                    <dd class="font-mono text-sm font-bold">${{ number_format($order->grand_total, 2) }} {{ $order->currency }}</dd>
                </div>
                <button type="button" class="shrink-0 text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 hover:bg-slate-50"
                        @click="navigator.clipboard.writeText('{{ number_format($order->grand_total, 2) }}'); copied = true; setTimeout(() => copied = false, 1500)">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" class="text-green-600">Copied!</span>
                </button>
            </div>
        </dl>

        @if($instructions['note'])
            <div class="mt-4 px-3 py-2 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs">
                {{ $instructions['note'] }}
            </div>
        @endif

        @if($order->payment_method === 'usdt_manual')
            <div class="mt-4 px-3 py-2 bg-red-50 border border-red-200 text-red-800 rounded-lg text-xs">
                Send on the <strong>TRC20 (Tron)</strong> network only. Sending USDT on Ethereum (ERC20),
                BNB Smart Chain (BEP20), or any other network to this address will result in permanently
                lost funds — the address is network-specific.
            </div>
        @endif

        @if($supportContact)
            <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500">
                Trouble paying? Contact <span class="font-medium text-slate-700">{{ $supportContact }}</span>
            </div>
        @endif
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="font-semibold mb-4">Upload proof of payment</h2>

        @if($existingProof && $existingProof->status === 'pending')
            <div class="px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
                Proof already submitted, awaiting admin review.
                <a href="{{ route('checkout.manual.pending', $order) }}" class="underline font-medium">View status</a>
            </div>
        @else
            @if($existingProof && $existingProof->status === 'rejected')
                <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm mb-4">
                    Your previous submission wasn't approved{{ $existingProof->admin_note ? ': ' . $existingProof->admin_note : '.' }}
                    Please upload a corrected proof below.
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.manual.submit', $order) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium mb-1">Screenshot or receipt</label>
                    <input type="file" name="proof" accept="image/*" required class="text-sm">
                    <p class="text-xs text-slate-400 mt-1">A screenshot of the transfer confirmation. Max 4MB.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">
                        {{ $order->payment_method === 'usdt_manual' ? 'Transaction hash (TxID)' : 'Sender name / transfer reference' }}
                    </label>
                    <input type="text" name="reference_note" required maxlength="255"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <button class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                    Submit for review
                </button>
            </form>
        @endif
    </div>
</div>
@endsection

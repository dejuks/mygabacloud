@extends('layouts.seller')
@section('title', 'Payouts')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <p class="text-xs uppercase text-slate-500">Available to withdraw</p>
            <p class="text-3xl font-bold text-green-600 mt-1">${{ number_format($wallet->available_balance, 2) }}</p>
            <p class="text-xs text-slate-400 mt-2">
                ${{ number_format($wallet->pending_balance, 2) }} still in the holding period
            </p>
        </div>

        <form method="POST" action="{{ route('seller.payouts.store') }}"
              class="bg-white border border-slate-200 rounded-xl p-6 space-y-4" x-data="{ method: 'paypal' }">
            @csrf
            <h2 class="font-bold">Request a payout</h2>

            <div>
                <label class="block text-sm font-medium mb-1">Amount ($)</label>
                <input type="number" step="0.01" name="amount" min="{{ $minimum }}"
                       max="{{ $wallet->available_balance }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-slate-400 mt-1">Minimum ${{ number_format($minimum, 2) }}.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Method</label>
                <select name="method" x-model="method" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="paypal">PayPal</option>
                    <option value="bank_transfer">Bank transfer</option>
                </select>
            </div>

            <div x-show="method === 'paypal'">
                <label class="block text-sm font-medium mb-1">PayPal email</label>
                <input type="email" name="paypal_email" value="{{ auth()->user()->paypal_email }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div x-show="method === 'bank_transfer'" x-cloak>
                <label class="block text-sm font-medium mb-1">Bank details</label>
                <textarea name="bank_details" rows="4" placeholder="Account name, number, bank, SWIFT"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>

            <button class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700"
                    @disabled($wallet->available_balance < $minimum)>
                Request payout
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-bold mb-4">Payout history</h2>
            @forelse($payouts as $payout)
                <div class="flex justify-between items-center py-3 border-b border-slate-100 last:border-0">
                    <div>
                        <p class="font-medium text-sm">${{ number_format($payout->amount, 2) }}</p>
                        <p class="text-xs text-slate-400">
                            {{ $payout->created_at->format('M j, Y') }} &middot; {{ str_replace('_',' ', $payout->method) }}
                        </p>
                        @if($payout->admin_note)
                            <p class="text-xs text-slate-500 mt-1">{{ $payout->admin_note }}</p>
                        @endif
                    </div>
                    @php
                        $colors = [
                            'pending' => 'bg-amber-100 text-amber-700',
                            'approved' => 'bg-blue-100 text-blue-700',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'paid' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="text-xs px-2 py-1 rounded-full {{ $colors[$payout->status] ?? '' }}">
                        {{ ucfirst($payout->status) }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No payout requests yet.</p>
            @endforelse
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-bold mb-4">Recent wallet activity</h2>
            @forelse($ledger as $entry)
                <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0 text-sm">
                    <div>
                        <span>{{ ucfirst(str_replace('_', ' ', $entry->type)) }}</span>
                        <span class="text-xs text-slate-400 ml-2">{{ $entry->created_at->format('M j') }}</span>
                    </div>
                    <span class="{{ $entry->amount >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                        {{ $entry->amount >= 0 ? '+' : '' }}${{ number_format(abs($entry->amount), 2) }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No activity yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

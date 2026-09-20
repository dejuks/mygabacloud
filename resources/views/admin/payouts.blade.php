@extends('layouts.admin')
@section('title', 'Payouts')

@section('content')
<div class="flex justify-end mb-4">
    <form method="POST" action="{{ route('admin.payouts.release') }}"
          onsubmit="return confirm('Release all earnings past the holding period to available balance?')">
        @csrf
        <button class="border border-slate-300 px-4 py-2 rounded-lg text-sm hover:bg-slate-50">
            Release cleared earnings
        </button>
    </form>
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <h2 class="font-bold mb-4">Open requests ({{ $pending->total() }})</h2>

    @forelse($pending as $payout)
        <div class="border-b border-slate-100 py-4 last:border-0">
            <div class="flex justify-between items-start gap-4 flex-wrap">
                <div>
                    <p class="font-semibold text-lg">${{ number_format($payout->amount, 2) }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $payout->seller->sellerProfile->store_name ?? $payout->seller->name }}
                        &middot; {{ $payout->seller->email }}
                        &middot; {{ $payout->created_at->diffForHumans() }}
                    </p>
                    <div class="text-xs text-slate-600 mt-2 bg-slate-50 rounded px-3 py-2 font-mono">
                        {{ str_replace('_', ' ', $payout->method) }}:
                        @foreach($payout->destination_details as $k => $v)
                            {{ $v }}
                        @endforeach
                    </div>
                </div>

                <div class="shrink-0 flex flex-col gap-2 w-56">
                    @if($payout->status === 'pending')
                        <form method="POST" action="{{ route('admin.payouts.approve', $payout) }}">
                            @csrf
                            <button class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm hover:bg-blue-700">
                                Approve
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.payouts.paid', $payout) }}" class="space-y-2">
                            @csrf
                            <input type="text" name="gateway_payout_id" placeholder="Transaction reference"
                                   class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs">
                            <button class="w-full bg-green-600 text-white py-2 rounded-lg text-sm hover:bg-green-700">
                                Mark as paid
                            </button>
                        </form>
                    @endif

                    <details>
                        <summary class="text-center border border-red-300 text-red-600 py-2 rounded-lg text-sm cursor-pointer hover:bg-red-50">
                            Reject
                        </summary>
                        <form method="POST" action="{{ route('admin.payouts.reject', $payout) }}" class="mt-2 space-y-1">
                            @csrf
                            <textarea name="admin_note" rows="2" required placeholder="Reason"
                                      class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                            <button class="w-full bg-red-600 text-white py-1 rounded-lg text-xs hover:bg-red-700">Confirm</button>
                        </form>
                    </details>

                    <span class="text-xs text-center text-slate-400">Status: {{ $payout->status }}</span>
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">No open payout requests.</p>
    @endforelse
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="font-bold mb-4">History</h2>
    @forelse($history as $payout)
        <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0 text-sm">
            <div>
                <span class="font-medium">${{ number_format($payout->amount, 2) }}</span>
                <span class="text-xs text-slate-400 ml-2">
                    {{ $payout->seller->name }} &middot; {{ $payout->processed_at?->format('M j, Y') }}
                </span>
            </div>
            <span class="text-xs px-2 py-1 rounded-full
                {{ $payout->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ ucfirst($payout->status) }}
            </span>
        </div>
    @empty
        <p class="text-sm text-slate-500">No history yet.</p>
    @endforelse
</div>
@endsection

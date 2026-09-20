@extends('layouts.admin')
@section('title', 'Manual payments')

@section('content')

<div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <h2 class="font-bold mb-1">Pending verification ({{ $pending->total() }})</h2>
    <p class="text-xs text-slate-500 mb-4">CBE, Telebirr and manual USDT transfers — verify against your bank/wallet before approving.</p>

    @forelse($pending as $proof)
        <div class="border-b border-slate-100 py-4 last:border-0">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex gap-4">
                    <a href="{{ route('admin.manual-payments.proof', $proof) }}" target="_blank"
                       class="w-24 h-24 bg-slate-100 rounded-lg overflow-hidden shrink-0 border border-slate-200 hover:border-indigo-400">
                        <img src="{{ route('admin.manual-payments.proof', $proof) }}" class="w-full h-full object-cover" alt="Payment proof">
                    </a>
                    <div>
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                                {{ \App\Models\ManualPaymentProof::methodLabel($proof->method) }}
                            </span>
                            <span class="font-semibold">${{ number_format($proof->order->grand_total, 2) }}</span>
                            @if(!empty($duplicateFlags[$proof->id]))
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                                    ⚠️ Reference already used on an approved payment
                                </span>
                            @endif
                        </div>
                        <p class="text-sm">{{ $proof->order->order_number }} &middot; {{ $proof->order->buyer->name }} ({{ $proof->order->buyer->email }})</p>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $proof->method === 'usdt_manual' ? 'TxID' : 'Reference' }}:
                            <span class="font-mono">{{ $proof->reference_note }}</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-1">Submitted {{ $proof->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div class="shrink-0 flex flex-col gap-2 w-44">
                    <form method="POST" action="{{ route('admin.manual-payments.approve', $proof) }}"
                          onsubmit="return confirm('Confirm you have verified this payment really arrived?')">
                        @csrf
                        <button class="w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                            Approve & unlock
                        </button>
                    </form>
                    <details>
                        <summary class="text-center border border-red-300 text-red-600 py-2 rounded-lg text-sm cursor-pointer hover:bg-red-50">
                            Reject
                        </summary>
                        <form method="POST" action="{{ route('admin.manual-payments.reject', $proof) }}" class="mt-2 space-y-1">
                            @csrf
                            <textarea name="admin_note" rows="2" required placeholder="Reason (shown to buyer)"
                                      class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                            <button class="w-full bg-red-600 text-white py-1.5 rounded-lg text-xs hover:bg-red-700">Confirm reject</button>
                        </form>
                    </details>
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">Nothing waiting on verification.</p>
    @endforelse

    <div class="mt-4">{{ $pending->links() }}</div>
</div>

<div class="bg-white border border-slate-200 rounded-2xl p-6">
    <h2 class="font-bold mb-4">History</h2>
    @forelse($history as $proof)
        <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0 text-sm">
            <div>
                <span class="font-medium">${{ number_format($proof->order->grand_total, 2) }}</span>
                <span class="text-xs text-slate-400 ml-2">
                    {{ \App\Models\ManualPaymentProof::methodLabel($proof->method) }} &middot;
                    {{ $proof->order->buyer->name }} &middot;
                    {{ $proof->reviewed_at?->format('M j, Y') }}
                </span>
                @if($proof->admin_note && $proof->status !== 'approved')
                    <p class="text-xs text-slate-500 mt-0.5">{{ $proof->admin_note }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs px-2 py-1 rounded-full {{ match($proof->status) {
                    'approved' => 'bg-green-100 text-green-700',
                    'revoked' => 'bg-slate-200 text-slate-600',
                    default => 'bg-red-100 text-red-700',
                } }}">
                    {{ ucfirst($proof->status) }}
                </span>
                @if($proof->status === 'approved')
                    <details class="relative">
                        <summary class="text-xs text-red-600 hover:underline cursor-pointer list-none">Revoke</summary>
                        <form method="POST" action="{{ route('admin.manual-payments.revoke', $proof) }}"
                              class="absolute right-0 mt-1 z-10 w-72 bg-white border border-slate-200 rounded-lg shadow-lg p-3 space-y-2"
                              onsubmit="return confirm('Revoke this payment? This reverses the seller\'s earnings for it and removes the buyer\'s access immediately.')">
                            @csrf
                            <textarea name="admin_note" rows="2" required placeholder="Reason for revoking"
                                      class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                            <button class="w-full bg-red-600 text-white py-1.5 rounded-lg text-xs hover:bg-red-700">Confirm revoke</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">No history yet.</p>
    @endforelse
</div>
@endsection

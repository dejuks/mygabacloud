@extends('layouts.admin')
@section('title', 'Settings')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
        <h2 class="font-bold mb-3">License</h2>
        @if($license)
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xs px-2 py-1 rounded-full {{ $licenseValid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $licenseValid ? 'Valid for this server' : 'Invalid — server mismatch' }}
                </span>
            </div>
            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">License key</dt><dd class="font-mono text-xs">{{ substr($license['key'], 0, 24) }}...</dd></div>
                @if($license['buyer_name'])
                    <div class="flex justify-between"><dt class="text-slate-500">Licensed to</dt><dd>{{ $license['buyer_name'] }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-slate-500">Issued</dt><dd>{{ \Illuminate\Support\Carbon::parse($license['issued_at'])->format('M j, Y') }}</dd></div>
            </dl>
        @else
            <p class="text-sm text-slate-500">No license on file.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5">
            <h2 class="font-bold">Marketplace</h2>

            <div>
                <label class="block text-sm font-medium mb-1">Default commission rate (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="default_commission_rate"
                       value="{{ old('default_commission_rate', $settings['default_commission_rate'] ?? 30) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-slate-400 mt-1">
                    Taken from every sale unless the seller has a custom rate. Changing this never affects past sales.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Minimum payout amount ($)</label>
                <input type="number" step="0.01" min="0" name="minimum_payout_amount"
                       value="{{ old('minimum_payout_amount', $settings['minimum_payout_amount'] ?? 50) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Payout holding period (days)</label>
                <input type="number" min="0" max="180" name="payout_holding_days"
                       value="{{ old('payout_holding_days', $settings['payout_holding_days'] ?? 14) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-slate-400 mt-1">
                    How long earnings stay pending before becoming withdrawable. Protects against chargebacks.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Currency code</label>
                <input type="text" maxlength="3" name="site_currency"
                       value="{{ old('site_currency', $settings['site_currency'] ?? 'USD') }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm uppercase">
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5">
            <div>
                <h2 class="font-bold">CBE Bank Transfer</h2>
                <p class="text-xs text-slate-400 mt-1">
                    Shown to buyers who choose "CBE Bank Transfer" at checkout. Leave blank to hide the line.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Account holder</label>
                <input type="text" name="cbe_account_holder" value="{{ old('cbe_account_holder', $settings['cbe_account_holder'] ?? '') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Account number</label>
                <input type="text" name="cbe_account_number" value="{{ old('cbe_account_number', $settings['cbe_account_number'] ?? '') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Branch (optional)</label>
                <input type="text" name="cbe_branch" value="{{ old('cbe_branch', $settings['cbe_branch'] ?? '') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Extra note (optional)</label>
                <textarea name="cbe_note" rows="2"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('cbe_note', $settings['cbe_note'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5">
            <div>
                <h2 class="font-bold">Telebirr</h2>
                <p class="text-xs text-slate-400 mt-1">
                    Shown to buyers who choose "Telebirr" at checkout.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Account holder</label>
                <input type="text" name="telebirr_account_holder" value="{{ old('telebirr_account_holder', $settings['telebirr_account_holder'] ?? '') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Telebirr number</label>
                <input type="text" name="telebirr_number" value="{{ old('telebirr_number', $settings['telebirr_number'] ?? '') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Extra note (optional)</label>
                <textarea name="telebirr_note" rows="2"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('telebirr_note', $settings['telebirr_note'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5">
            <div>
                <h2 class="font-bold">USDT (TRC20)</h2>
                <p class="text-xs text-slate-400 mt-1">
                    Shown to buyers who choose "USDT (TRC20)" at checkout. Double-check this address —
                    a typo here sends buyers' money somewhere unrecoverable.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Account holder</label>
                <input type="text" name="usdt_account_holder" value="{{ old('usdt_account_holder', $settings['usdt_account_holder'] ?? '') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">TRC20 wallet address</label>
                <input type="text" name="usdt_wallet_address" value="{{ old('usdt_wallet_address', $settings['usdt_wallet_address'] ?? '') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Extra note (optional)</label>
                <textarea name="usdt_note" rows="2" placeholder="e.g. include your order number as the memo, if your wallet supports it"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('usdt_note', $settings['usdt_note'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-3">
            <div>
                <h2 class="font-bold">Payment support contact</h2>
                <p class="text-xs text-slate-400 mt-1">
                    Shown on every manual-payment instructions page, so buyers who hit trouble paying
                    (wrong amount sent, can't find the transfer option, etc.) have somewhere to reach you.
                </p>
            </div>
            <input type="text" name="payment_support_contact"
                   value="{{ old('payment_support_contact', $settings['payment_support_contact'] ?? '') }}"
                   placeholder="e.g. Telegram @yourhandle or WhatsApp +251..."
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <button class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
            Save settings
        </button>
    </form>
</div>
@endsection

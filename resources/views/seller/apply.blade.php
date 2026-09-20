@extends('layouts.app')
@section('title', 'Become a seller')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-2">Become a seller</h1>
    <p class="text-slate-500 text-sm mb-6">Sell your scripts, themes and templates to thousands of developers.</p>

    @if($profile)
        @if($profile->application_status === 'pending')
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                <h2 class="font-semibold text-amber-900 mb-1">Application under review</h2>
                <p class="text-sm text-amber-800">
                    We received your application for <strong>{{ $profile->store_name }}</strong>
                    on {{ $profile->created_at->format('M j, Y') }}. We will email you once it is reviewed.
                </p>
            </div>
        @elseif($profile->application_status === 'rejected')
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <h2 class="font-semibold text-red-900 mb-1">Application not approved</h2>
                <p class="text-sm text-red-800">Unfortunately your application was not approved at this time.</p>
            </div>
        @else
            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                <h2 class="font-semibold text-green-900 mb-2">You are an approved seller</h2>
                <a href="{{ route('seller.dashboard') }}" class="text-sm text-green-800 underline">Go to your dashboard</a>
            </div>
        @endif
    @else
        <form method="POST" action="{{ route('seller.apply') }}"
              class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">Store name</label>
                <input type="text" name="store_name" value="{{ old('store_name') }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-slate-400 mt-1">This is how buyers will see you.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tell us about yourself</label>
                <textarea name="description" rows="5" required minlength="50"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('description') }}</textarea>
                <p class="text-xs text-slate-400 mt-1">At least 50 characters. What do you build?</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Website or portfolio (optional)</label>
                <input type="url" name="website" value="{{ old('website') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">PayPal email (for payouts)</label>
                <input type="email" name="paypal_email" value="{{ old('paypal_email', auth()->user()->paypal_email) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <button class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                Submit application
            </button>
        </form>
    @endif
</div>
@endsection

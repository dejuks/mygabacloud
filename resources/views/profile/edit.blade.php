@php
    $profileLayout = auth()->user()->is_admin
        ? 'layouts.admin'
        : (auth()->user()->isSeller() ? 'layouts.seller' : 'layouts.app');
@endphp
@extends($profileLayout)
@section('title', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    @if($profileLayout === 'layouts.app')
        <h1 class="text-2xl font-bold">My Profile</h1>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h2 class="font-bold mb-4">Account details</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                @unless(auth()->user()->hasVerifiedEmail())
                    <p class="text-xs text-amber-600 mt-1">Your email is not verified.</p>
                @endunless
                <p class="text-xs text-slate-400 mt-1">Changing your email will require re-verifying it.</p>
            </div>

            <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                Save changes
            </button>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6" id="password">
        <h2 class="font-bold mb-4">Change password</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-1">Current password</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">New password</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Confirm new password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                Change password
            </button>
        </form>
    </div>
</div>
@endsection

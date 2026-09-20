@extends('install.layout')

@section('content')
<h2 class="text-xl font-bold text-slate-800 mb-1">Create your admin account</h2>
<p class="text-slate-500 text-sm mb-6">This is what you'll use to log into the admin panel.</p>

<form method="POST" action="{{ route('install.admin.store') }}" class="space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium mb-1">Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Password</label>
        <input type="password" name="password" required minlength="8"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        <p class="text-xs text-slate-400 mt-1">At least 8 characters.</p>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Confirm password</label>
        <input type="password" name="password_confirmation" required minlength="8"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div class="flex justify-between pt-4">
        <a href="{{ route('install.settings') }}" class="text-slate-500 px-6 py-3 text-sm font-medium hover:text-slate-700">Back</a>
        <button class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Continue
        </button>
    </div>
</form>
@endsection

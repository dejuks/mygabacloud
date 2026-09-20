@extends('install.layout')

@section('content')
<h2 class="text-xl font-bold text-slate-800 mb-1">Site & mail settings</h2>
<p class="text-slate-500 text-sm mb-6">You can always change these later from Admin &gt; Settings.</p>

<form method="POST" action="{{ route('install.settings.store') }}" class="space-y-4" x-data="{ mailEnabled: false }">
    @csrf

    <div>
        <label class="block text-sm font-medium mb-1">Site name</label>
        <input type="text" name="app_name" value="{{ old('app_name', config('app.name')) }}" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Site URL</label>
        <input type="url" name="app_url" value="{{ old('app_url', config('app.url')) }}" required
               placeholder="https://yourdomain.com"
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Currency code</label>
        <input type="text" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" required
               class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm uppercase">
    </div>

    <div class="border-t border-slate-100 pt-4">
        <label class="flex items-center gap-2 text-sm cursor-pointer mb-3">
            <input type="checkbox" name="mail_enabled" value="1" x-model="mailEnabled">
            <span class="font-medium">Set up email now (SMTP)</span>
        </label>
        <p class="text-xs text-slate-400 mb-3" x-show="!mailEnabled">
            You can skip this — order confirmations and notifications will just be logged instead of
            emailed until you configure this from Admin &gt; Settings.
        </p>

        <div x-show="mailEnabled" x-cloak class="space-y-3 bg-slate-50 rounded-lg p-4">
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-medium mb-1">SMTP host</label>
                    <input type="text" name="mail_host" value="{{ old('mail_host') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Port</label>
                    <input type="text" name="mail_port" value="{{ old('mail_port', '587') }}"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Username</label>
                <input type="text" name="mail_username" value="{{ old('mail_username') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Password</label>
                <input type="password" name="mail_password" value="{{ old('mail_password') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Encryption</label>
                <select name="mail_encryption" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="tls">TLS (port 587)</option>
                    <option value="ssl">SSL (port 465)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">From address</label>
                <input type="email" name="mail_from_address" value="{{ old('mail_from_address') }}"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="flex justify-between pt-4">
        <a href="{{ route('install.database') }}" class="text-slate-500 px-6 py-3 text-sm font-medium hover:text-slate-700">Back</a>
        <button class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Continue
        </button>
    </div>
</form>
@endsection

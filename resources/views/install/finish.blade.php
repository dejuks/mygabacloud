@extends('install.layout')

@section('content')
<div x-data="{
    running: false, done: false, errored: false, message: '',
    async runInstall() {
        this.running = true; this.errored = false;
        try {
            const res = await fetch('{{ route('install.run') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) {
                this.done = true;
                setTimeout(() => window.location.href = json.redirect, 1200);
            } else {
                this.errored = true;
                this.message = json.message;
                this.running = false;
            }
        } catch (e) {
            this.errored = true;
            this.message = 'Something went wrong reaching the server.';
            this.running = false;
        }
    }
}">
    <div class="text-center" x-show="!running && !done">
        <h2 class="text-xl font-bold text-slate-800 mb-2">Ready to install</h2>
        <p class="text-slate-500 text-sm mb-8">
            This will create your database tables, save your settings, create your admin account,
            and generate your license. It only takes a moment.
        </p>

        <div x-show="errored" x-cloak class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm text-left" x-text="message"></div>

        <button @click="runInstall()"
                class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Install now
        </button>
        <div class="mt-4">
            <a href="{{ route('install.license') }}" class="text-slate-500 text-sm font-medium hover:text-slate-700">Back</a>
        </div>
    </div>

    <div class="text-center py-8" x-show="running && !done" x-cloak>
        <svg class="animate-spin w-10 h-10 text-indigo-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="text-slate-600 font-medium">Installing your marketplace...</p>
        <p class="text-slate-400 text-sm mt-1">This can take up to a minute.</p>
    </div>

    <div class="text-center py-8" x-show="done" x-cloak>
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="text-slate-800 font-bold text-lg">All done!</p>
        <p class="text-slate-500 text-sm mt-1">Taking you to your admin dashboard...</p>
    </div>
</div>
@endsection

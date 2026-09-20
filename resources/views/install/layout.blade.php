<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup &middot; {{ config('app.name', 'Marketplace') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 min-h-screen">

@php
    $stepLabels = [
        'welcome' => 'Welcome',
        'requirements' => 'Requirements',
        'database' => 'Database',
        'settings' => 'Site & Mail',
        'admin' => 'Admin Account',
        'license' => 'License',
        'finish' => 'Install',
    ];
    $currentIndex = array_search($current, $steps);
@endphp

<div class="bg-gradient-to-br from-indigo-600 via-indigo-600 to-purple-700">
    <div class="max-w-4xl mx-auto px-4 pt-10 pb-8 text-center">
        <h1 class="text-2xl font-bold text-white">{{ config('app.name', 'Marketplace') }} Setup</h1>
        <p class="text-indigo-100 text-sm mt-1">Get your marketplace running in a few minutes.</p>
    </div>

    <div class="max-w-4xl mx-auto px-4 pb-6">
        <div class="flex items-center">
            @foreach($steps as $i => $step)
                @php $done = $i < $currentIndex; $active = $i === $currentIndex; @endphp
                <div class="flex items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                                {{ $done ? 'bg-white text-indigo-600' : ($active ? 'bg-white text-indigo-600 ring-4 ring-white/30' : 'bg-white/20 text-white') }}">
                        @if($done)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    @if($i < count($steps) - 1)
                        <div class="flex-1 h-0.5 mx-1 {{ $done ? 'bg-white' : 'bg-white/20' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="text-center text-indigo-100 text-xs mt-2 font-medium uppercase tracking-wide">
            Step {{ $currentIndex + 1 }} of {{ count($steps) }} &middot; {{ $stepLabels[$current] ?? '' }}
        </p>
    </div>
</div>

<div class="max-w-2xl mx-auto px-4 -mt-4 pb-16">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        @if(session('error'))
            <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</div>

</body>
</html>

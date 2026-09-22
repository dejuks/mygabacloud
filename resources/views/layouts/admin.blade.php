<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') &middot; {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen" x-data="{ sidebarOpen: false }">

@php
    // Computed here directly (rather than requiring every admin controller to
    // remember to pass these) so the sidebar badges are always accurate.
    $pendingProductsCount = \App\Models\Product::pendingReview()->count()
        + \App\Models\ProductRestoreRequest::where('status', 'pending')->count();
    $pendingSellersCount = \App\Models\SellerProfile::where('application_status', 'pending')->count();
    $pendingManualPaymentsCount = \App\Models\ManualPaymentProof::where('status', 'pending')->count();
    $pendingEngagementCount = \App\Models\EngagementUnlock::where('status', 'pending')->count();
    $pendingPayoutsCount = \App\Models\PayoutRequest::where('status', 'pending')->count();

    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'Overview', 'icon' => 'grid'],
        ['route' => 'admin.products.index', 'label' => 'All Products', 'icon' => 'box'],
        ['route' => 'admin.products.pending', 'label' => 'Product queue', 'icon' => 'box', 'badge' => $pendingProductsCount ?? null],
        ['route' => 'admin.sellers.index', 'label' => 'Sellers', 'icon' => 'store', 'badge' => $pendingSellersCount ?? null],
        ['route' => 'admin.manual-payments.index', 'label' => 'Manual payments', 'icon' => 'bank', 'badge' => $pendingManualPaymentsCount ?? null],
        ['route' => 'admin.engagement.index', 'label' => 'Free unlocks', 'icon' => 'youtube', 'badge' => $pendingEngagementCount ?? null],
        ['route' => 'admin.payouts.index', 'label' => 'Payouts', 'icon' => 'wallet', 'badge' => $pendingPayoutsCount ?? null],
        ['route' => 'admin.categories.index', 'label' => 'Categories', 'icon' => 'tag'],
        ['route' => 'admin.reports.index', 'label' => 'Reports', 'icon' => 'chart'],
        ['route' => 'admin.app-download.edit', 'label' => 'App Download', 'icon' => 'phone'],
        ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'gear'],
    ];

    $icons = [
        'grid' => '<path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z" stroke-linecap="round" stroke-linejoin="round"/>',
        'box' => '<path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8M12 13v8" stroke-linecap="round" stroke-linejoin="round"/>',
        'store' => '<path d="M3 9l1-5h16l1 5M3 9a2 2 0 002 2 2 2 0 002-2 2 2 0 002 2 2 2 0 002-2 2 2 0 002 2 2 2 0 002-2 2 2 0 002 2 2 2 0 002-2M4 9v9a1 1 0 001 1h14a1 1 0 001-1V9" stroke-linecap="round" stroke-linejoin="round"/>',
        'bank' => '<path d="M3 21h18M3 10h18M5 6l7-4 7 4M4 10v11m16-11v11M8 14v3m4-3v3m4-3v3" stroke-linecap="round" stroke-linejoin="round"/>',
        'youtube' => '<path d="M22.5 12s0-3.5-.4-5.2c-.3-1-1.1-1.7-2-2C18.2 4.3 12 4.3 12 4.3s-6.2 0-8.1.5c-1 .3-1.7 1-2 2C1.5 8.5 1.5 12 1.5 12s0 3.5.4 5.2c.3 1 1.1 1.7 2 2 1.9.5 8.1.5 8.1.5s6.2 0 8.1-.5c1-.3 1.7-1 2-2 .4-1.7.4-5.2.4-5.2z" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.8 15.3l5.5-3.3-5.5-3.3v6.6z" fill="currentColor" stroke="none"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 12a2 2 0 000 4h4v-4h-4z" stroke-linecap="round" stroke-linejoin="round"/>',
        'chart' => '<path d="M3 3v18h18M8 17V9m5 8V5m5 12v-6" stroke-linecap="round" stroke-linejoin="round"/>',
        'tag' => '<path d="M20.59 13.41L11 3.83a2 2 0 00-1.42-.58H4a1 1 0 00-1 1v5.58a2 2 0 00.59 1.42l9.58 9.58a2 2 0 002.83 0l4.59-4.59a2 2 0 000-2.83z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'phone' => '<rect x="7" y="2" width="10" height="20" rx="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M11 18h2" stroke-linecap="round"/><path d="M12 6v6m0 0l-2.5-2.5M12 12l2.5-2.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'gear' => '<path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 005 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.16.31.49.51.85.51a1.65 1.65 0 001.51-1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
@endphp

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex flex-col w-64 shrink-0 bg-white border-r border-slate-200 h-screen sticky top-0">
        <div class="h-16 flex items-center px-6 border-b border-slate-100">
            <a href="{{ route('home') }}" class="font-bold text-lg tracking-tight text-slate-800">
                {{ config('app.name') }} <span class="text-indigo-500 font-normal text-sm">Admin</span>
            </a>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @foreach($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-[18px] h-[18px] shrink-0 {{ $active ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        {!! $icons[$item['icon']] !!}
                    </svg>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if(!empty($item['badge']))
                        <span class="bg-amber-400 text-amber-950 text-xs font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>

    </aside>

    {{-- Mobile sidebar --}}
    <div x-show="sidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex">
        <div class="fixed inset-0 bg-black/50" @click="sidebarOpen = false"></div>
        <aside class="relative w-64 bg-white flex flex-col">
            <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100">
                <span class="font-bold text-slate-800">{{ config('app.name') }} Admin</span>
                <button @click="sidebarOpen = false" class="text-slate-400">&times;</button>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs($item['route']) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                        {{ $item['label'] }}
                        @if(!empty($item['badge']))
                            <span class="ml-auto bg-amber-400 text-amber-950 text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </aside>
    </div>

    {{-- Main --}}
    <div class="flex-1 min-w-0 flex flex-col">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center px-4 lg:px-8 gap-4">
            <button @click="sidebarOpen = true" class="lg:hidden text-slate-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="font-bold text-lg text-slate-800">@yield('title', 'Admin')</h1>

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('home') }}" title="Back to site"
                   class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18" stroke-linecap="round"/>
                    </svg>
                </a>

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-800 pl-1 pr-2 py-1 rounded-full hover:bg-slate-100">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold text-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 mt-2 w-52 bg-white border border-slate-200 rounded-lg shadow-lg py-1 z-20">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">My Profile</a>
                        <a href="{{ route('profile.edit') }}#password" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Change Password</a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-slate-50">Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>

@if(session('success'))
    <div x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 6000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         class="fixed bottom-6 right-6 z-50 max-w-sm bg-slate-900 text-white rounded-xl shadow-lg px-5 py-4 flex items-start gap-3">
        <span class="text-green-400 text-lg leading-none mt-0.5">✓</span>
        <p class="text-sm flex-1">{{ session('success') }}</p>
        <button @click="show = false" class="text-slate-400 hover:text-white leading-none">&times;</button>
    </div>
@endif

@stack('scripts')

</body>
</html>

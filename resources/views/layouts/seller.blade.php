<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Seller Dashboard') &middot; {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen" x-data="{ sidebarOpen: false }">

@php
    $sellerNavItems = [
        ['route' => 'seller.dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
        ['route' => 'seller.products.index', 'label' => 'My Products', 'icon' => 'box'],
        ['route' => 'seller.reports.index', 'label' => 'Analytics', 'icon' => 'chart'],
        ['route' => 'seller.payouts.index', 'label' => 'Payouts', 'icon' => 'wallet'],
    ];

    $sellerIcons = [
        'grid' => '<path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z" stroke-linecap="round" stroke-linejoin="round"/>',
        'box' => '<path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8M12 13v8" stroke-linecap="round" stroke-linejoin="round"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 12a2 2 0 000 4h4v-4h-4z" stroke-linecap="round" stroke-linejoin="round"/>',
        'plus' => '<path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/>',
        'chart' => '<path d="M3 3v18h18M8 17V9m5 8V5m5 12v-6" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    $storeName = auth()->user()->sellerProfile->store_name ?? auth()->user()->name;
@endphp

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex flex-col w-64 shrink-0 bg-white border-r border-slate-200 h-screen sticky top-0">
        <div class="h-16 flex items-center px-6 border-b border-slate-100">
            <a href="{{ route('home') }}" class="font-bold text-lg tracking-tight truncate text-slate-800">
                {{ config('app.name') }} <span class="text-fuchsia-500 font-normal text-sm">Seller</span>
            </a>
        </div>

        <div class="px-6 py-4 border-b border-slate-100">
            <p class="text-xs text-slate-400 uppercase tracking-wide">Store</p>
            <p class="font-semibold truncate text-slate-800">{{ $storeName }}</p>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @foreach($sellerNavItems as $item)
                @php
                    $active = $item['route'] === 'seller.products.index'
                        ? request()->routeIs('seller.products.*')
                        : request()->routeIs($item['route']);
                @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ $active ? 'bg-fuchsia-50 text-fuchsia-700' : 'text-slate-600 hover:bg-slate-50' }}">
                    <svg class="w-[18px] h-[18px] shrink-0 {{ $active ? 'text-fuchsia-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        {!! $sellerIcons[$item['icon']] !!}
                    </svg>
                    <span class="flex-1">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <a href="{{ route('seller.products.create') }}"
               class="flex items-center gap-3 px-3 py-2.5 mt-3 rounded-lg text-sm font-medium border border-dashed border-fuchsia-300 text-fuchsia-700 hover:bg-fuchsia-50">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    {!! $sellerIcons['plus'] !!}
                </svg>
                <span>New product</span>
            </a>
        </nav>
    </aside>

    {{-- Mobile sidebar --}}
    <div x-show="sidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex">
        <div class="fixed inset-0 bg-black/50" @click="sidebarOpen = false"></div>
        <aside class="relative w-64 bg-white flex flex-col">
            <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100">
                <span class="font-bold text-slate-800">{{ config('app.name') }} Seller</span>
                <button @click="sidebarOpen = false" class="text-slate-400">&times;</button>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                @foreach($sellerNavItems as $item)
                    @php
                        $mobileActive = $item['route'] === 'seller.products.index'
                            ? request()->routeIs('seller.products.*')
                            : request()->routeIs($item['route']);
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ $mobileActive ? 'bg-fuchsia-50 text-fuchsia-700' : 'text-slate-600' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
                <a href="{{ route('seller.products.create') }}"
                   class="flex items-center gap-3 px-3 py-2.5 mt-3 rounded-lg text-sm font-medium border border-dashed border-fuchsia-300 text-fuchsia-700">
                    New product
                </a>
            </nav>
        </aside>
    </div>

    {{-- Main --}}
    <div class="flex-1 min-w-0 flex flex-col">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center px-4 lg:px-8 gap-4">
            <button @click="sidebarOpen = true" class="lg:hidden text-slate-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="font-bold text-lg text-slate-800">@yield('title', 'Seller Dashboard')</h1>

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
                        <div class="w-8 h-8 rounded-full bg-fuchsia-100 text-fuchsia-700 flex items-center justify-center font-semibold text-xs">
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

@stack('scripts')

</body>
</html>

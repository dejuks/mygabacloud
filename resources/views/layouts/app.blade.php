<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    @if(config('services.adsense.publisher_id'))
        <meta name="google-adsense-account" content="{{ config('services.adsense.publisher_id') }}">
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('services.adsense.publisher_id') }}"
                crossorigin="anonymous"></script>
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

@php
    // Shared here (rather than a global view composer touching a stock
    // Laravel file) so every page gets live category data with no extra
    // setup step to forget.
    $navCategories = \App\Models\Category::whereNull('parent_id')
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->take(8)
        ->get();
@endphp

<header class="sticky top-0 z-40">
    {{-- Top bar: dark, logo + search + account --}}
    <div class="bg-slate-900 text-slate-200">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-6">
            <a href="{{ route('home') }}" class="text-xl font-bold text-white shrink-0">
                {{ config('app.name') }}
            </a>

            <form action="{{ route('products.index') }}" method="GET" class="flex-1 max-w-xl hidden md:flex">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search scripts, themes, templates..."
                       class="w-full px-4 py-2 rounded-l-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <button class="bg-indigo-600 hover:bg-indigo-500 px-4 rounded-r-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                </button>
            </form>

            <div class="ml-auto flex items-center gap-5 text-sm shrink-0">
                <a href="{{ route('cart.index') }}" class="relative hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    @php $cartCount = app(\App\Services\CartService::class)->count(); @endphp
                    @if($cartCount > 0)
                        <span class="absolute -top-2 -right-2 bg-indigo-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>

                @auth
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-1.5 hover:text-white">
                            <span class="w-6 h-6 rounded-full bg-indigo-500 text-white text-xs font-semibold flex items-center justify-center">
                                {{ strtoupper(substr(auth()->user()?->name ?? '?', 0, 1)) }}
                            </span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute right-0 mt-2 w-52 bg-white text-slate-700 border border-slate-200 rounded-lg shadow-lg py-1">
                            <a href="{{ route('library.index') }}" class="block px-4 py-2 hover:bg-slate-50">My Library</a>

                            @if(auth()->user()?->isSeller())
                                <a href="{{ route('seller.dashboard') }}" class="block px-4 py-2 hover:bg-slate-50">Seller Dashboard</a>
                                <a href="{{ route('seller.products.index') }}" class="block px-4 py-2 hover:bg-slate-50">My Products</a>
                                <a href="{{ route('seller.payouts.index') }}" class="block px-4 py-2 hover:bg-slate-50">Payouts</a>
                            @else
                                <a href="{{ route('seller.apply.form') }}" class="block px-4 py-2 hover:bg-slate-50">Become a Seller</a>
                            @endif

                            @if(auth()->user()?->is_admin)
                                <div class="border-t my-1"></div>
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-slate-50 text-indigo-600 font-medium">Admin Panel</a>
                            @endif

                            <div class="border-t my-1"></div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-slate-50">My Profile</a>
                            <a href="{{ route('profile.edit') }}#password" class="block px-4 py-2 hover:bg-slate-50">Change Password</a>

                            <div class="border-t my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 hover:bg-slate-50 text-red-600">Log out</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="hover:text-white">Log in</a>
                    <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-500">Sign up</a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Sub-nav: light, category browse --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 h-11 flex items-center gap-6 text-sm">
            <div x-data="{ open: false }" class="relative shrink-0">
                <button @click="open = !open" class="flex items-center gap-1 font-medium text-slate-700 hover:text-indigo-600">
                    Browse
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                     class="absolute left-0 mt-2 w-56 bg-white border border-slate-200 rounded-lg shadow-lg py-1 z-20">
                    <a href="{{ route('products.index') }}" class="block px-4 py-2 hover:bg-slate-50 font-medium">All Products</a>
                    <div class="border-t my-1"></div>
                    @foreach($navCategories as $navCat)
                        <a href="{{ route('products.index', ['category' => $navCat->slug]) }}"
                           class="block px-4 py-2 hover:bg-slate-50">{{ $navCat->name }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Only this inner row scrolls horizontally — keeping overflow off
                 the outer row means the dropdown above isn't clipped by it. --}}
            <div class="flex items-center gap-6 overflow-x-auto min-w-0">
                @foreach($navCategories as $navCat)
                    <a href="{{ route('products.index', ['category' => $navCat->slug]) }}"
                       class="shrink-0 text-slate-600 hover:text-indigo-600 whitespace-nowrap">{{ $navCat->name }}</a>
                @endforeach
            </div>

            @unless(auth()->check() && auth()->user()->isSeller())
                <a href="{{ route('seller.apply.form') }}" class="ml-auto shrink-0 font-medium text-indigo-600 hover:text-indigo-700 whitespace-nowrap">
                    Start Selling
                </a>
            @else
                <a href="{{ route('seller.dashboard') }}" class="ml-auto shrink-0 font-medium text-indigo-600 hover:text-indigo-700 whitespace-nowrap">
                    Seller Dashboard
                </a>
            @endunless
        </div>
    </div>
</header>

<main class="flex-1">
    @hasSection('full-width')
        @yield('content')
    @else
        <div class="max-w-7xl mx-auto px-4 py-6">
            @include('partials.flash')
            @yield('content')
        </div>
    @endif
</main>

<footer class="bg-slate-900 text-slate-400 mt-12">
    <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-4 gap-8 text-sm">
        <div>
            <p class="text-white font-bold text-lg mb-2">{{ config('app.name') }}</p>
            <p class="text-slate-400">Digital scripts, templates, and themes built by developers, for developers.</p>
        </div>
        <div>
            <p class="text-white font-medium mb-2">Marketplace</p>
            <div class="space-y-1.5">
                <a href="{{ route('products.index') }}" class="block hover:text-white">Browse all products</a>
                <a href="{{ route('seller.apply.form') }}" class="block hover:text-white">Become a seller</a>
            </div>
        </div>
        <div>
            <p class="text-white font-medium mb-2">Categories</p>
            <div class="space-y-1.5">
                @foreach($navCategories->take(4) as $navCat)
                    <a href="{{ route('products.index', ['category' => $navCat->slug]) }}" class="block hover:text-white">{{ $navCat->name }}</a>
                @endforeach
            </div>
        </div>
        <div>
            <p class="text-white font-medium mb-2">Account</p>
            <div class="space-y-1.5">
                @auth
                    <a href="{{ route('library.index') }}" class="block hover:text-white">My library</a>
                @else
                    <a href="{{ route('login') }}" class="block hover:text-white">Log in</a>
                    <a href="{{ route('register') }}" class="block hover:text-white">Sign up</a>
                @endauth
                <a href="{{ route('pages.about') }}" class="block hover:text-white">About us</a>
                <a href="{{ route('pages.contact') }}" class="block hover:text-white">Contact us</a>
            </div>
        </div>
    </div>
    <div class="border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <div class="flex gap-4">
                <a href="{{ route('pages.privacy') }}" class="hover:text-white">Privacy Policy</a>
                <a href="{{ route('pages.terms') }}" class="hover:text-white">Terms of Service</a>
                <a href="{{ route('pages.contact') }}" class="hover:text-white">Contact</a>
            </div>
        </div>
    </div>
</footer>

<div x-data="{ show: !localStorage.getItem('cookie_consent') }"
     x-show="show" x-cloak
     class="fixed bottom-0 inset-x-0 z-50 bg-slate-900 text-slate-200 border-t border-slate-700">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center gap-4">
        <p class="text-sm flex-1">
            We use cookies to keep you logged in, remember your cart, and — where advertising is
            enabled — to show relevant ads. See our
            <a href="{{ route('pages.privacy') }}" class="text-indigo-400 hover:underline">Privacy Policy</a> for details.
        </p>
        <button @click="localStorage.setItem('cookie_consent', '1'); show = false"
                class="shrink-0 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2 rounded-lg">
            Got it
        </button>
    </div>
</div>

@stack('scripts')
</body>
</html>

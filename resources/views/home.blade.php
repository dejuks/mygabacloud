@extends('layouts.app')
@section('title', 'Buy App Templates, PHP Scripts, WordPress Themes and more')
@section('full-width', true)

@section('content')

{{-- Hero: full-bleed gradient with search --}}
<section class="relative bg-gradient-to-br from-indigo-600 via-indigo-600 to-blue-600 overflow-hidden">
    <div class="absolute inset-0 opacity-[0.08]" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 28px 28px;"></div>

    <div class="relative max-w-5xl mx-auto px-4 py-20 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4 leading-tight">
            Buy premium <span class="whitespace-nowrap">PHP scripts</span>, app templates,
            themes and plugins<br class="hidden md:block"> and build something great.
        </h1>

        <form action="{{ route('products.index') }}" method="GET" class="max-w-2xl mx-auto mt-8 flex shadow-lg rounded-lg overflow-hidden">
            <select name="category" class="hidden sm:block bg-white text-slate-600 text-sm px-4 border-r border-slate-200 focus:outline-none">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->slug }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <input type="text" name="q" placeholder="e.g. &quot;invoicing script&quot; or &quot;admin dashboard&quot;"
                   class="flex-1 px-4 py-3.5 text-slate-800 text-sm focus:outline-none">
            <button class="bg-slate-900 hover:bg-slate-800 px-6 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
            </button>
        </form>

        <div class="flex items-center justify-center gap-3 mt-8 flex-wrap">
            @guest
                <a href="{{ route('register') }}" class="bg-white text-indigo-700 font-semibold px-6 py-3 rounded-lg hover:bg-indigo-50">
                    Create a free account
                </a>
            @endguest
            <a href="{{ route('products.index') }}" class="border border-white/40 text-white font-medium px-6 py-3 rounded-lg hover:bg-white/10">
                Browse products
            </a>
            <a href="{{ route('seller.apply.form') }}" class="border border-white/40 text-white font-medium px-6 py-3 rounded-lg hover:bg-white/10">
                Sell your work
            </a>
        </div>
    </div>
</section>

{{-- Announcement strip --}}
<div class="bg-slate-900 text-center py-3 text-sm text-slate-300">
    @guest
        <a href="{{ route('register') }}" class="text-indigo-400 font-semibold hover:underline">Create an account</a>
        today and start building your download library.
    @else
        Explore <span class="text-white font-semibold">{{ $categories->sum('products_count') }}+</span> products
        across {{ $categories->count() }} categories — new items added every week.
    @endguest
</div>

<div class="max-w-7xl mx-auto px-4 py-10">

@if($featured->isNotEmpty())
<section class="mb-12">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold flex items-center gap-2">
            <span class="text-amber-500">★</span> Featured
        </h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($featured as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

@if($categories->isNotEmpty())
<section class="mb-12">
    <h2 class="text-xl font-bold mb-4">Browse categories</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        @foreach($categories as $category)
            <a href="{{ route('products.index', ['category' => $category->slug]) }}"
               class="bg-white border border-slate-200 rounded-xl p-4 text-center hover:border-indigo-400 hover:shadow transition">
                <div class="font-medium text-sm">{{ $category->name }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $category->products_count }} items</div>
            </a>
        @endforeach
    </div>
</section>
@endif

@if($bestSellers->isNotEmpty())
<section class="mb-12">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4h2v12H4V4zm5 0h2v12H9V4zm5 0h2v12h-2V4z"/></svg>
            Featured Items
        </h2>
        <a href="{{ route('products.index', ['sort' => 'popular']) }}" class="text-sm text-indigo-600 hover:underline">Browse all items</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($bestSellers as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

@if($latest->isNotEmpty())
<section class="mb-12">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Newest arrivals</h2>
        <a href="{{ route('products.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($latest as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

@guest
<section class="bg-white border border-slate-200 rounded-2xl p-8 text-center">
    <h2 class="text-2xl font-bold mb-2">Start selling your code</h2>
    <p class="text-slate-500 mb-5 max-w-lg mx-auto">
        Upload your scripts and themes, set your price, and earn on every sale.
    </p>
    <a href="{{ route('register') }}" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700">
        Create a free account
    </a>
</section>
@endguest

</div>
@endsection

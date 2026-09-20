@extends('layouts.app')
@section('title', 'Browse products')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <aside class="lg:col-span-1">
        <form method="GET" action="{{ route('products.index') }}"
              class="bg-white border border-slate-200 rounded-xl p-5 space-y-5 sticky top-20">

            <input type="hidden" name="q" value="{{ request('q') }}">

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-2">Category</label>
                <select name="category" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                            {{ $category->name }} ({{ $category->products_count }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-2">Price range</label>
                <div class="flex gap-2">
                    <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max"
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            @if($frameworks->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-2">Format</label>
                <select name="framework" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Any</option>
                    @foreach($frameworks as $fw)
                        <option value="{{ $fw }}" @selected(request('framework') === $fw)>{{ $fw }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-2">Minimum rating</label>
                <select name="min_rating" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Any rating</option>
                    @foreach([4 => '4 stars & up', 3 => '3 stars & up'] as $val => $label)
                        <option value="{{ $val }}" @selected(request('min_rating') == $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                Apply filters
            </button>
            <a href="{{ route('products.index') }}" class="block text-center text-xs text-slate-500 hover:underline">
                Clear all
            </a>
        </form>
    </aside>

    <div class="lg:col-span-3">
        <div class="flex items-center justify-between mb-5">
            <p class="text-sm text-slate-500">
                {{ $products->total() }} {{ Str::plural('product', $products->total()) }} found
                @if(request('q')) for "{{ request('q') }}" @endif
            </p>

            <form method="GET" id="sortForm">
                @foreach(request()->except('sort', 'page') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <select name="sort" onchange="document.getElementById('sortForm').submit()"
                        class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Newest</option>
                    <option value="popular" @selected(request('sort')==='popular')>Most popular</option>
                    <option value="rating" @selected(request('sort')==='rating')>Highest rated</option>
                    <option value="price_low" @selected(request('sort')==='price_low')>Price: low to high</option>
                    <option value="price_high" @selected(request('sort')==='price_high')>Price: high to low</option>
                </select>
            </form>
        </div>

        @if($products->isEmpty())
            <div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
                <p class="text-slate-500">No products match your filters.</p>
                <a href="{{ route('products.index') }}" class="text-indigo-600 text-sm hover:underline mt-2 inline-block">
                    Clear filters
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', $product->title)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    {{-- Main column --}}
    <div class="lg:col-span-2 space-y-6">

        <div>
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}"
               class="text-xs text-indigo-600 font-medium hover:underline">{{ $product->category->name }}</a>
            <h1 class="text-3xl font-bold mt-1 mb-2">{{ $product->title }}</h1>
            <div class="flex items-center gap-4 text-sm text-slate-500">
                <span>by <strong class="text-slate-700">{{ $product->seller->sellerProfile->store_name ?? $product->seller->name }}</strong></span>
                @if($product->reviews_count > 0)
                    <span class="text-amber-500">&#9733; {{ number_format($product->average_rating, 1) }} ({{ $product->reviews_count }})</span>
                @endif
                <span>{{ $product->sales_count }} sales</span>
                <span>{{ $product->views_count }} views</span>
            </div>
        </div>

        {{-- Gallery --}}
        <div x-data="{ active: '{{ $product->thumbnail ? Storage::url($product->thumbnail) : '' }}' }">
            <div class="aspect-video bg-slate-100 rounded-xl overflow-hidden border border-slate-200">
                <template x-if="active">
                    <img :src="active" class="w-full h-full object-cover">
                </template>
            </div>

            @if($product->images->isNotEmpty())
            <div class="flex gap-2 mt-3 overflow-x-auto">
                @if($product->thumbnail)
                    <button @click="active = '{{ Storage::url($product->thumbnail) }}'"
                            class="w-24 h-16 rounded-lg overflow-hidden border-2 border-transparent hover:border-indigo-500 shrink-0">
                        <img src="{{ Storage::url($product->thumbnail) }}" class="w-full h-full object-cover">
                    </button>
                @endif
                @foreach($product->images as $image)
                    <button @click="active = '{{ Storage::url($image->path) }}'"
                            class="w-24 h-16 rounded-lg overflow-hidden border-2 border-transparent hover:border-indigo-500 shrink-0">
                        <img src="{{ Storage::url($image->path) }}" class="w-full h-full object-cover">
                    </button>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Description --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-bold text-lg mb-3">Description</h2>
            <div class="prose prose-sm max-w-none text-slate-700 whitespace-pre-line">{{ $product->description }}</div>
        </div>

        {{-- Changelog --}}
        @if($product->changelogs->isNotEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-bold text-lg mb-3">Changelog</h2>
            <div class="space-y-4">
                @foreach($product->changelogs as $log)
                    <div class="border-l-2 border-indigo-200 pl-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-sm">v{{ $log->version }}</span>
                            <span class="text-xs text-slate-400">{{ $log->created_at->format('M j, Y') }}</span>
                        </div>
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $log->notes }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Reviews --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h2 class="font-bold text-lg mb-4">Reviews ({{ $product->reviews_count }})</h2>

            @if($purchasedItem)
                <form method="POST" action="{{ route('reviews.store', $product) }}"
                      class="mb-6 p-4 bg-slate-50 rounded-lg space-y-3">
                    @csrf
                    <input type="hidden" name="order_item_id" value="{{ $purchasedItem->id }}">
                    <p class="text-sm font-medium">You bought this — leave a review</p>
                    <select name="rating" required class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Rating...</option>
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                    <textarea name="comment" rows="3" placeholder="What did you think?"
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
                        Submit review
                    </button>
                </form>
            @endif

            @forelse($product->reviews as $review)
                <div class="border-b border-slate-100 py-4 last:border-0">
                    <div class="flex items-center gap-3 mb-1">
                        <span class="font-medium text-sm">{{ $review->user->name }}</span>
                        <span class="text-amber-500 text-sm">
                            {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                        </span>
                        <span class="text-xs text-slate-400">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-slate-600">{{ $review->comment }}</p>

                    @if($review->seller_reply)
                        <div class="mt-3 ml-4 pl-4 border-l-2 border-indigo-200">
                            <p class="text-xs font-semibold text-indigo-600 mb-1">Seller response</p>
                            <p class="text-sm text-slate-600">{{ $review->seller_reply }}</p>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No reviews yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Sidebar: buy box --}}
    <div class="lg:col-span-1">
        <div class="bg-white border border-slate-200 rounded-xl p-6 sticky top-20">

            @php $owned = auth()->check() && auth()->user()->licenses()->where('product_id', $product->id)->where('status','active')->exists(); @endphp

            @if($owned)
                <p class="text-center text-green-700 bg-green-50 border border-green-200 rounded-lg py-3 text-sm mb-4">
                    You own this product
                </p>
                <a href="{{ route('library.index') }}"
                   class="block text-center bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                    Go to library
                </a>
            @elseif($product->seller_id === auth()->id())
                <p class="text-center text-slate-500 text-sm mb-4">This is your own product.</p>
                <a href="{{ route('seller.products.edit', $product) }}"
                   class="block text-center border border-slate-300 py-3 rounded-lg font-medium hover:bg-slate-50">
                    Edit product
                </a>
            @else
                <form method="POST" action="{{ route('cart.add', $product) }}" x-data="{ license: 'regular' }">
                    @csrf

                    <label class="flex items-start gap-3 p-3 border-2 rounded-lg cursor-pointer mb-2"
                           :class="license === 'regular' ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200'">
                        <input type="radio" name="license_type" value="regular" x-model="license" class="mt-1">
                        <div class="flex-1">
                            <div class="flex justify-between items-baseline">
                                <span class="font-semibold text-sm">Regular License</span>
                                @if($product->isOnSale())
                                    <span class="flex items-baseline gap-1.5">
                                        <span class="text-xs text-slate-400 line-through">${{ number_format($product->regular_price, 2) }}</span>
                                        <span class="font-bold text-rose-600">${{ number_format($product->sale_price, 2) }}</span>
                                    </span>
                                @else
                                    <span class="font-bold">${{ number_format($product->regular_price, 2) }}</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Use in a single free or commercial project.</p>
                            @if($product->isOnSale())
                                <p class="text-xs text-rose-600 font-medium mt-1">
                                    🔥 {{ $product->discountPercent() }}% off
                                    @if($product->sale_ends_at)
                                        — ends {{ $product->sale_ends_at->format('M j, g:ia') }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </label>

                    @if($product->extended_price)
                    <label class="flex items-start gap-3 p-3 border-2 rounded-lg cursor-pointer mb-4"
                           :class="license === 'extended' ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200'">
                        <input type="radio" name="license_type" value="extended" x-model="license" class="mt-1">
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <span class="font-semibold text-sm">Extended License</span>
                                <span class="font-bold">${{ number_format($product->extended_price, 2) }}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Use in a project sold to end users.</p>
                        </div>
                    </label>
                    @endif

                    @auth
                        <button class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                            Add to cart
                        </button>
                    @else
                        <a href="{{ route('login') }}"
                           class="block text-center w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                            Log in to buy
                        </a>
                    @endauth
                </form>

                @if($product->allow_free_unlock)
                    <div class="mt-4 pt-4 border-t border-dashed">
                        <a href="{{ route('unlock.create', $product) }}"
                           class="block text-center border-2 border-dashed border-green-400 text-green-700 bg-green-50 py-2.5 rounded-lg font-medium text-sm hover:bg-green-100">
                            🎬 Get it free — watch & engage on YouTube
                        </a>
                        <p class="text-xs text-slate-400 text-center mt-1.5">
                            No payment needed. Manually reviewed within 24 hours.
                        </p>
                    </div>
                @endif
            @endif

            @if($product->demo_url)
                <a href="{{ $product->demo_url }}" target="_blank" rel="noopener noreferrer"
                   class="block text-center border border-slate-300 py-3 rounded-lg font-medium mt-3 hover:bg-slate-50">
                    Live preview
                </a>
            @endif

            <dl class="mt-6 space-y-2 text-sm border-t pt-4">
                <div class="flex justify-between"><dt class="text-slate-500">Version</dt><dd>{{ $product->current_version }}</dd></div>
                @if($product->framework)
                <div class="flex justify-between"><dt class="text-slate-500">Format</dt><dd>{{ $product->framework }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-slate-500">Last updated</dt><dd>{{ $product->updated_at->format('M j, Y') }}</dd></div>
                @if($product->compatible_with)
                <div>
                    <dt class="text-slate-500 mb-1">Compatible with</dt>
                    <dd class="flex flex-wrap gap-1">
                        @foreach($product->compatible_with as $item)
                            <span class="text-xs bg-slate-100 px-2 py-1 rounded">{{ $item }}</span>
                        @endforeach
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
</div>

@if($related->isNotEmpty())
<section class="mt-12">
    <h2 class="text-xl font-bold mb-4">Related products</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($related as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

@endsection

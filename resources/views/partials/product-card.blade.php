<a href="{{ route('products.show', $product) }}"
   class="group bg-white border border-slate-200 rounded-lg overflow-hidden hover:shadow-lg hover:border-indigo-300 transition flex flex-col">
    <div class="relative aspect-video bg-slate-100 overflow-hidden">
        @if($product->thumbnail)
            <img src="{{ Storage::url($product->thumbnail) }}" alt="{{ $product->title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition">
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-400 text-sm">No preview</div>
        @endif

        @if($product->isOnSale())
            <span class="absolute top-2 right-2 bg-rose-600 text-white text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded">
                -{{ $product->discountPercent() }}%
            </span>
        @elseif($product->sales_count >= 20)
            <span class="absolute top-2 right-2 bg-rose-600 text-white text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded">
                Popular
            </span>
        @endif
    </div>

    <div class="p-4 flex flex-col flex-1">
        <div class="text-xs text-indigo-600 font-medium mb-1">{{ $product->category->name ?? '' }}</div>
        <h3 class="font-semibold text-sm leading-snug mb-2 line-clamp-2 text-slate-800">{{ $product->title }}</h3>

        {{-- Icon row: sales count, extended license, demo availability --}}
        <div class="flex items-center gap-3 text-slate-400 text-xs mb-2">
            <span class="flex items-center gap-1" title="Sales">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                {{ $product->sales_count }}
            </span>
            @if($product->extended_price)
                <span class="flex items-center gap-1" title="Extended license available">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Extended
                </span>
            @endif
            @if($product->demo_url)
                <span class="flex items-center gap-1" title="Live preview available">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Demo
                </span>
            @endif
        </div>

        <div class="flex items-center gap-1 mb-3">
            @if($product->reviews_count > 0)
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-3.5 h-3.5 {{ $i <= round($product->average_rating) ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                @endfor
                <span class="text-xs text-slate-400 ml-1">({{ $product->reviews_count }})</span>
            @else
                <span class="text-xs text-slate-300">No reviews yet</span>
            @endif
        </div>

        <div class="mt-auto flex items-center justify-between pt-3 border-t border-slate-100">
            <span class="text-xs text-slate-400 truncate max-w-[45%]">
                {{ $product->seller->sellerProfile->store_name ?? $product->seller->name }}
            </span>
            @if($product->isOnSale())
                <span class="flex items-baseline gap-1.5">
                    <span class="text-xs text-slate-400 line-through">${{ number_format($product->regular_price, 2) }}</span>
                    <span class="text-sm font-bold text-rose-600 bg-rose-50 px-2.5 py-1 rounded">
                        ${{ number_format($product->sale_price, 2) }}
                    </span>
                </span>
            @else
                <span class="text-sm font-bold text-slate-900 bg-slate-100 group-hover:bg-indigo-50 group-hover:text-indigo-700 px-2.5 py-1 rounded transition">
                    ${{ number_format($product->regular_price, 2) }}
                </span>
            @endif
        </div>
    </div>
</a>

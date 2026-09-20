@extends('layouts.admin')
@section('title', 'All Products')

@section('content')

<div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
    <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <div class="md:col-span-2">
            <label class="block text-xs font-medium mb-1">Search title</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..."
                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium mb-1">Status</label>
            <select name="status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All (excl. deleted)</option>
                @foreach(['draft', 'pending_review', 'approved', 'rejected', 'suspended', 'deleted'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium mb-1">Seller</label>
            <select name="seller_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All sellers</option>
                @foreach($sellers as $seller)
                    <option value="{{ $seller->user_id }}" @selected((int) request('seller_id') === $seller->user_id)>{{ $seller->store_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
                Apply filters
            </button>
        </div>
    </form>
</div>

<div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="text-left px-5 py-3">Product</th>
                <th class="text-left px-5 py-3">Seller</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-left px-5 py-3">Price</th>
                <th class="text-left px-5 py-3">Sales</th>
                <th class="text-right px-5 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($products as $product)
            <tr class="{{ $product->trashed() ? 'opacity-50' : '' }}">
                <td class="px-5 py-4">
                    <p class="font-medium">
                        {{ $product->title }}
                        @if($product->is_featured)
                            <span class="text-xs text-amber-600" title="Featured">★</span>
                        @endif
                        @if($product->isOnSale())
                            <span class="text-xs px-1.5 py-0.5 rounded bg-rose-100 text-rose-700">-{{ $product->discountPercent() }}%</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-400">{{ $product->category->name ?? '' }}</p>
                </td>
                <td class="px-5 py-4">
                    {{ $product->seller->sellerProfile->store_name ?? $product->seller->name ?? '—' }}
                </td>
                <td class="px-5 py-4">
                    @php
                        $colors = [
                            'draft' => 'bg-slate-100 text-slate-600',
                            'pending_review' => 'bg-amber-100 text-amber-700',
                            'approved' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            'suspended' => 'bg-slate-200 text-slate-600',
                        ];
                    @endphp
                    @if($product->trashed())
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-300 text-slate-700">Deleted</span>
                    @else
                        <span class="text-xs px-2 py-1 rounded-full {{ $colors[$product->status] ?? '' }}">
                            {{ ucfirst(str_replace('_', ' ', $product->status)) }}
                        </span>
                    @endif
                </td>
                <td class="px-5 py-4">
                    @if($product->isOnSale())
                        <span class="line-through text-slate-400 text-xs">${{ number_format($product->regular_price, 2) }}</span>
                        <span class="font-semibold text-rose-600">${{ number_format($product->sale_price, 2) }}</span>
                    @else
                        ${{ number_format($product->regular_price, 2) }}
                    @endif
                </td>
                <td class="px-5 py-4">{{ $product->sales_count }}</td>
                <td class="px-5 py-4 text-right whitespace-nowrap">
                    @unless($product->trashed())
                        <a href="{{ route('admin.products.edit', $product) }}" class="text-indigo-600 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('admin.products.toggle-featured', $product) }}" class="inline ml-3">
                            @csrf
                            <button class="text-amber-600 hover:underline">{{ $product->is_featured ? 'Unfeature' : 'Feature' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline ml-3"
                              onsubmit="return confirm('Delete or unlist this product? If it has sales it will only be unlisted, not permanently deleted.')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Delete</button>
                        </form>
                    @else
                        <span class="text-xs text-slate-400">Permanently deleted</span>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No products match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-6">{{ $products->links() }}</div>
@endsection

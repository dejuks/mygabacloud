@extends('layouts.seller')
@section('title', 'My products')

@section('content')
<div class="flex items-center justify-end mb-6">
    <a href="{{ route('seller.products.create') }}"
       class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
        + New product
    </a>
</div>

@if($products->isEmpty())
    <div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
        <p class="text-slate-500 mb-4">You have not created any products yet.</p>
        <a href="{{ route('seller.products.create') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Create your first product
        </a>
    </div>
@else
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="text-left px-5 py-3">Product</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-left px-5 py-3">Price</th>
                <th class="text-left px-5 py-3">Sales</th>
                <th class="text-right px-5 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        @foreach($products as $product)
            <tr>
                <td class="px-5 py-4">
                    <p class="font-medium">{{ $product->title }}</p>
                    <p class="text-xs text-slate-400">{{ $product->category->name ?? '' }}</p>
                    @if($product->status === 'rejected' && $product->rejection_reason)
                        <p class="text-xs text-red-600 mt-1">Reason: {{ $product->rejection_reason }}</p>
                    @endif
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
                    <span class="text-xs px-2 py-1 rounded-full {{ $colors[$product->status] ?? '' }}">
                        {{ ucfirst(str_replace('_', ' ', $product->status)) }}
                    </span>
                </td>
                <td class="px-5 py-4">${{ number_format($product->regular_price, 2) }}</td>
                <td class="px-5 py-4">{{ $product->sales_count }}</td>
                <td class="px-5 py-4 text-right whitespace-nowrap">
                    <a href="{{ route('seller.products.edit', $product) }}" class="text-indigo-600 hover:underline">Edit</a>

                    @if(in_array($product->status, ['draft', 'rejected', 'suspended']))
                        <form method="POST" action="{{ route('seller.products.submit', $product) }}" class="inline ml-3">
                            @csrf
                            <button class="text-green-600 hover:underline">
                                {{ $product->status === 'suspended' ? 'Relist' : 'Submit' }}
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('seller.products.destroy', $product) }}" class="inline ml-3"
                          onsubmit="return confirm('Delete this product?')">
                        @csrf @method('DELETE')
                        <button class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<div class="mt-6">{{ $products->links() }}</div>
@endif

@if($deletedProducts->isNotEmpty())
<div class="mt-10">
    <h2 class="text-lg font-bold mb-1">Deleted products</h2>
    <p class="text-sm text-slate-500 mb-4">
        You can request an admin to restore any of these. Restoring always sends the product back
        through review before it goes live again.
    </p>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="text-left px-5 py-3">Product</th>
                    <th class="text-left px-5 py-3">Deleted</th>
                    <th class="text-right px-5 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($deletedProducts as $product)
                <tr>
                    <td class="px-5 py-4">
                        <p class="font-medium">{{ $product->title }}</p>
                        <p class="text-xs text-slate-400">{{ $product->category->name ?? '' }}</p>
                    </td>
                    <td class="px-5 py-4 text-slate-500">{{ $product->deleted_at->diffForHumans() }}</td>
                    <td class="px-5 py-4 text-right">
                        @if($pendingRestoreProductIds->contains($product->id))
                            <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700">Restore requested</span>
                        @else
                            <details class="relative inline-block text-left">
                                <summary class="text-xs text-indigo-600 hover:underline cursor-pointer list-none">Request restore</summary>
                                <form method="POST" action="{{ route('seller.products.restore-request', $product->id) }}"
                                      class="absolute right-0 mt-1 z-10 w-72 bg-white border border-slate-200 rounded-lg shadow-lg p-3 space-y-2 text-left">
                                    @csrf
                                    <textarea name="reason" rows="2" required minlength="5" placeholder="Why should this be restored?"
                                              class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                                    <button class="w-full bg-indigo-600 text-white py-1.5 rounded-lg text-xs hover:bg-indigo-700">Submit request</button>
                                </form>
                            </details>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
@endif
@endsection

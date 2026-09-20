@extends('layouts.admin')
@section('title', 'Edit: ' . $product->title)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to all products</a>
        <h1 class="text-2xl font-bold mt-1">
            {{ $product->title }}
            <span class="text-sm font-normal text-slate-400">by {{ $product->seller->sellerProfile->store_name ?? $product->seller->name }}</span>
        </h1>
    </div>
</div>

@if($product->status === 'pending_review')
    <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
        This product is awaiting review —
        <a href="{{ route('admin.products.review', $product) }}" class="underline font-medium">open the full review screen</a>
        to approve or reject it.
    </div>
@endif

<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
    @include('seller.products._form', ['isEdit' => true])
</form>
@endsection

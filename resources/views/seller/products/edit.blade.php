@extends('layouts.seller')
@section('title', 'Edit product')

@section('content')
@if(in_array($product->status, ['draft', 'rejected']))
    <div class="flex items-center justify-end mb-6">
        <form method="POST" action="{{ route('seller.products.submit', $product) }}">
            @csrf
            <button class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                Submit for review
            </button>
        </form>
    </div>
@endif

@if($product->status === 'rejected' && $product->rejection_reason)
    <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        <strong>Not approved:</strong> {{ $product->rejection_reason }}
    </div>
@elseif($product->status === 'pending_review')
    <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
        This product is awaiting admin review.
    </div>
@endif

<form method="POST" action="{{ route('seller.products.update', $product) }}" enctype="multipart/form-data">
    @include('seller.products._form', ['isEdit' => true])
</form>
@endsection

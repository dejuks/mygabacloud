@extends('layouts.admin')
@section('title', 'Product review queue')

@section('content')
@if($products->isEmpty())
    <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center">
        <p class="text-slate-500">Nothing in the review queue. All caught up.</p>
    </div>
@else
<div class="space-y-4">
    @foreach($products as $product)
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="flex gap-5">
                <div class="w-32 h-20 bg-slate-100 rounded-lg overflow-hidden shrink-0">
                    @if($product->thumbnail)
                        <img src="{{ Storage::url($product->thumbnail) }}" class="w-full h-full object-cover">
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold">{{ $product->title }}</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $product->category->name ?? '' }} &middot;
                        ${{ number_format($product->regular_price, 2) }} &middot;
                        by {{ $product->seller->sellerProfile->store_name ?? $product->seller->name }}
                    </p>
                    <p class="text-sm text-slate-600 mt-2">{{ $product->short_description }}</p>

                    <div class="flex flex-wrap gap-3 mt-3 text-xs items-center">
                        @foreach($product->files as $file)
                            <span class="px-2 py-1 rounded
                                {{ $file->scan_status === 'clean' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $file->original_name }} &mdash; {{ $file->scan_status }}
                            </span>
                            @if($file->integrity_status === 'corrupted')
                                <span class="px-2 py-1 rounded bg-red-100 text-red-700 font-medium">⚠️ Corrupted file</span>
                            @endif
                            @if($file->duplicate_of_file_id)
                                <span class="px-2 py-1 rounded bg-red-100 text-red-700 font-medium">⚠️ Duplicate upload</span>
                            @endif
                            @if($file->scan_status !== 'clean')
                                <form method="POST" action="{{ route('admin.products.file.clean', [$product, $file->id]) }}">
                                    @csrf
                                    <button class="text-indigo-600 hover:underline">Mark clean</button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="shrink-0 flex flex-col gap-2 w-40">
                    <a href="{{ route('admin.products.review', $product) }}"
                       class="text-center border border-slate-300 py-2 rounded-lg text-sm hover:bg-slate-50">
                        Full review
                    </a>

                    <form method="POST" action="{{ route('admin.products.approve', $product) }}">
                        @csrf
                        <button class="w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                            Approve
                        </button>
                    </form>

                    <details>
                        <summary class="text-center border border-red-300 text-red-600 py-2 rounded-lg text-sm cursor-pointer hover:bg-red-50">
                            Reject
                        </summary>
                        <form method="POST" action="{{ route('admin.products.reject', $product) }}" class="mt-2">
                            @csrf
                            <textarea name="rejection_reason" rows="3" required minlength="10"
                                      placeholder="Why is this rejected?"
                                      class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                            <button class="w-full bg-red-600 text-white py-2 rounded-lg text-sm mt-1 hover:bg-red-700">
                                Confirm reject
                            </button>
                        </form>
                    </details>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-6">{{ $products->links() }}</div>
@endif

@if($restoreRequests->isNotEmpty())
<div class="mt-10">
    <h2 class="text-lg font-bold mb-4">Restore requests ({{ $restoreRequests->count() }})</h2>
    <div class="space-y-3">
        @foreach($restoreRequests as $req)
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="flex justify-between items-start gap-4 flex-wrap">
                    <div>
                        <p class="font-semibold">{{ $req->product->title ?? 'Deleted product' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $req->seller->name }} ({{ $req->seller->email }}) &middot;
                            deleted {{ $req->product->deleted_at?->diffForHumans() }}
                        </p>
                        <p class="text-sm text-slate-600 mt-2 max-w-xl">"{{ $req->reason }}"</p>
                        <p class="text-xs text-slate-400 mt-1">Requested {{ $req->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="shrink-0 flex flex-col gap-2 w-44">
                        <form method="POST" action="{{ route('admin.products.restore-requests.approve', $req) }}"
                              onsubmit="return confirm('Restore this product? It will go back into the review queue before going live.')">
                            @csrf
                            <button class="w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                                Restore
                            </button>
                        </form>
                        <details>
                            <summary class="text-center border border-red-300 text-red-600 py-2 rounded-lg text-sm cursor-pointer hover:bg-red-50">
                                Reject
                            </summary>
                            <form method="POST" action="{{ route('admin.products.restore-requests.reject', $req) }}" class="mt-2 space-y-1">
                                @csrf
                                <textarea name="admin_note" rows="2" required placeholder="Reason"
                                          class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs"></textarea>
                                <button class="w-full bg-red-600 text-white py-1.5 rounded-lg text-xs hover:bg-red-700">Confirm</button>
                            </form>
                        </details>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
@endsection

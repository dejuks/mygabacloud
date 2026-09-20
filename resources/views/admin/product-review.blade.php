@extends('layouts.admin')
@section('title', 'Review: ' . $product->title)

@section('content')
<a href="{{ route('admin.products.pending') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to queue</a>
<h1 class="text-2xl font-bold mt-2 mb-6">{{ $product->title }}</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold mb-3">Description</h2>
            <p class="text-sm text-slate-700 whitespace-pre-line">{{ $product->description }}</p>
        </div>

        @if($product->images->isNotEmpty())
        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold mb-3">Preview images</h2>
            <div class="grid grid-cols-3 gap-3">
                @foreach($product->images as $image)
                    <img src="{{ Storage::url($image->path) }}" class="w-full aspect-video object-cover rounded-lg">
                @endforeach
            </div>
        </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold mb-3">Uploaded files</h2>
            @forelse($product->files as $file)
                <div class="py-3 text-sm border-b border-slate-100 last:border-0">
                    <div class="flex justify-between items-start gap-3">
                        <div>
                            <p class="font-medium">{{ $file->original_name }}</p>
                            <p class="text-xs text-slate-400">
                                {{ round($file->size_bytes / 1048576, 1) }} MB &middot;
                                v{{ $file->version }} &middot;
                                sha256: {{ Str::limit($file->checksum, 16) }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="text-xs px-2 py-1 rounded {{ $file->scan_status === 'clean' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                malware: {{ $file->scan_status }}
                            </span>
                            @if($file->integrity_status !== 'pending')
                                <span class="text-xs px-2 py-1 rounded {{ $file->integrity_status === 'valid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    file: {{ $file->integrity_status }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-2 flex gap-3">
                        <a href="{{ route('admin.products.file.preview', [$product, $file]) }}" target="_blank"
                           class="text-xs text-indigo-600 hover:underline">
                            {{ strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION)) === 'pdf' ? 'Preview inline' : 'Download to review' }}
                        </a>
                    </div>

                    @if($file->integrity_status === 'corrupted')
                        <div class="mt-2 px-3 py-2 bg-red-50 border border-red-200 text-red-800 rounded-lg text-xs">
                            ⚠️ This file failed an integrity check — it may be truncated, corrupted, or not
                            actually the file type its name suggests. Do not approve without investigating.
                        </div>
                    @endif

                    @if($file->duplicateOf)
                        <div class="mt-2 px-3 py-2 bg-red-50 border border-red-200 text-red-800 rounded-lg text-xs">
                            ⚠️ <strong>Exact duplicate detected.</strong> This exact file (byte-for-byte,
                            verified by checksum) was already uploaded as
                            "<a href="{{ route('admin.products.review', $file->duplicateOf->product) }}" class="underline">{{ $file->duplicateOf->product->title }}</a>"
                            by {{ $file->duplicateOf->product->seller->name }}. This only catches identical
                            files — a re-packaged or renamed copy wouldn't trigger this.
                        </div>
                    @endif

                    @if($file->ai_review_notes)
                        <div class="mt-2 px-3 py-2 bg-indigo-50 border border-indigo-200 text-indigo-900 rounded-lg text-xs">
                            <strong>🤖 AI advisory (not a copyright determination):</strong> {{ $file->ai_review_notes }}
                        </div>
                    @endif

                    @if(! $file->analyzed_at)
                        <p class="text-xs text-slate-400 mt-1">Analysis pending — refresh in a moment.</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-red-600">No files uploaded. Do not approve.</p>
            @endforelse
        </div>
    </div>

    <div class="lg:col-span-1 space-y-5">
        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h2 class="font-bold mb-3">Details</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Seller</dt><dd>{{ $product->seller->sellerProfile->store_name ?? $product->seller->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Category</dt><dd>{{ $product->category->name ?? '' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Regular</dt><dd>${{ number_format($product->regular_price, 2) }}</dd></div>
                @if($product->extended_price)
                <div class="flex justify-between"><dt class="text-slate-500">Extended</dt><dd>${{ number_format($product->extended_price, 2) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-slate-500">Version</dt><dd>{{ $product->current_version }}</dd></div>
                @if($product->demo_url)
                <div class="flex justify-between"><dt class="text-slate-500">Demo</dt>
                    <dd><a href="{{ $product->demo_url }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">Open</a></dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-3">
            <form method="POST" action="{{ route('admin.products.approve', $product) }}">
                @csrf
                <button class="w-full bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700">
                    Approve and publish
                </button>
            </form>

            <form method="POST" action="{{ route('admin.products.reject', $product) }}" class="space-y-2">
                @csrf
                <textarea name="rejection_reason" rows="3" required minlength="10"
                          placeholder="Reason for rejection (sent to the seller)"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
                <button class="w-full border border-red-300 text-red-600 py-2 rounded-lg font-medium hover:bg-red-50">
                    Reject
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

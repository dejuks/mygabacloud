@extends('layouts.app')
@section('title', 'My library')

@section('content')
<h1 class="text-2xl font-bold mb-6">My library</h1>

@if($licenses->isEmpty())
    <div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
        <p class="text-slate-500 mb-4">You have not purchased anything yet.</p>
        <a href="{{ route('products.index') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Browse products
        </a>
    </div>
@else
<div class="space-y-4">
    @foreach($licenses as $license)
        <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col md:flex-row gap-5">
            <div class="w-full md:w-40 h-24 bg-slate-100 rounded-lg overflow-hidden shrink-0">
                @if($license->product->thumbnail)
                    <img src="{{ Storage::url($license->product->thumbnail) }}" class="w-full h-full object-cover">
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <a href="{{ route('products.show', $license->product) }}" class="font-semibold hover:text-indigo-600">
                    {{ $license->product->title }}
                </a>
                <p class="text-xs text-slate-500 mt-1">
                    {{ ucfirst($license->type) }} license &middot;
                    Purchased {{ $license->created_at->format('M j, Y') }} &middot;
                    v{{ $license->product->current_version }}
                </p>
                <div class="bg-slate-50 rounded-lg px-3 py-2 font-mono text-xs mt-3 inline-block">
                    {{ $license->license_key }}
                </div>
            </div>

            <div class="shrink-0 flex flex-col gap-2 justify-center">
                @if($license->product->files->where('scan_status', 'clean')->isNotEmpty())
                    <a href="{{ route('library.download', $license) }}"
                       class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 text-center">
                        Download
                    </a>
                @else
                    <span class="text-xs text-slate-400 text-center">File not ready</span>
                @endif
                <span class="text-xs text-slate-400 text-center">{{ $license->activations_count }} downloads</span>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-8">{{ $licenses->links() }}</div>
@endif
@endsection

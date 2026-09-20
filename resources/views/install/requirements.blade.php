@extends('install.layout')

@section('content')
<h2 class="text-xl font-bold text-slate-800 mb-1">Server requirements</h2>
<p class="text-slate-500 text-sm mb-6">Everything below needs a green check before continuing.</p>

<div class="space-y-2 mb-8">
    @foreach($checks as $check)
        <div class="flex items-center justify-between px-4 py-2.5 rounded-lg {{ $check['passed'] ? 'bg-green-50' : 'bg-red-50' }}">
            <span class="text-sm {{ $check['passed'] ? 'text-green-800' : 'text-red-800' }}">{{ $check['label'] }}</span>
            @if($check['passed'])
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            @else
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            @endif
        </div>
    @endforeach
</div>

@if(! $allPassed)
    <div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
        Fix the items marked with a red X, then reload this page.
    </div>
@endif

<div class="flex justify-between">
    <a href="{{ route('install.welcome') }}" class="text-slate-500 px-6 py-3 text-sm font-medium hover:text-slate-700">Back</a>
    @if($allPassed)
        <a href="{{ route('install.database') }}"
           class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-indigo-700">
            Continue
        </a>
    @else
        <button disabled class="bg-slate-200 text-slate-400 px-8 py-3 rounded-lg font-medium cursor-not-allowed">
            Continue
        </button>
    @endif
</div>
@endsection

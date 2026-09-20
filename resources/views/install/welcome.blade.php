@extends('install.layout')

@section('content')
<div class="text-center">
    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-5">
        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
    </div>
    <h2 class="text-xl font-bold text-slate-800 mb-2">Welcome</h2>
    <p class="text-slate-500 text-sm mb-8 max-w-md mx-auto">
        This wizard will check your server, connect your database, set up your site details,
        create your admin account, and generate your license — all in a few minutes.
    </p>

    <a href="{{ route('install.requirements') }}"
       class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-indigo-700">
        Get started
    </a>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Contact Us')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-2">Contact Us</h1>
    <p class="text-slate-500 mb-8">
        Questions about an order, a listing, becoming a seller, or anything else — send us a message
        and we'll get back to you.
    </p>

    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <form method="POST" action="{{ route('pages.contact.submit') }}" class="space-y-4">
            @csrf

            {{-- Honeypot: hidden from real visitors via CSS, bots that fill every field trip this --}}
            <div class="absolute -left-[9999px]" aria-hidden="true">
                <label for="company_website">Leave this field empty</label>
                <input type="text" name="company_website" id="company_website" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Your name</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Your email</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="200"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Message</label>
                <textarea name="message" rows="6" required minlength="10" maxlength="3000"
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">{{ old('message') }}</textarea>
            </div>

            <button class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                Send message
            </button>
        </form>
    </div>
</div>
@endsection

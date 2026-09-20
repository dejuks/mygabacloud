@extends('layouts.app')
@section('title', 'About Us')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">About {{ config('app.name') }}</h1>

    <div class="prose prose-slate max-w-none space-y-5 text-slate-700">
        <p>
            {{ config('app.name') }} is a marketplace for digital products — PHP scripts, mobile app
            templates, WordPress themes and plugins, UI kits, and increasingly documents and
            presentation templates too. Independent developers, designers, and small studios list
            their work here; buyers get vetted, ready-to-use digital goods with a license and
            support attached, instead of piecing something together from scratch or hunting through
            forums for a script that may or may not still work.
        </p>

        <h2 class="text-xl font-bold text-slate-900 pt-2">What we do</h2>
        <p>
            Every product listed here goes through a manual review before it's published — we check
            that the files actually work, that the listing accurately describes what a buyer is
            getting, and that uploaded files pass basic integrity checks before anyone can buy them.
            Sellers set their own prices and keep the majority of every sale; we take a transparent
            commission to keep the marketplace running, reviewed, and supported.
        </p>

        <h2 class="text-xl font-bold text-slate-900 pt-2">How buying works</h2>
        <p>
            Browse or search for what you need, check the preview images, demo link, and reviews from
            previous buyers, then purchase with a card, PayPal, or one of our local payment options.
            Your download and license key are available immediately in your library, with re-download
            access any time.
        </p>

        <h2 class="text-xl font-bold text-slate-900 pt-2">How selling works</h2>
        <p>
            Anyone with a verified account can apply to sell. Once approved, you can list products,
            set regular and extended license pricing, and track sales from your seller dashboard.
            Earnings are held briefly after each sale before becoming available for payout — this
            protects both buyers and sellers in the event of a dispute or a payment reversal.
        </p>

        <h2 class="text-xl font-bold text-slate-900 pt-2">Get in touch</h2>
        <p>
            Questions about a purchase, a listing, or anything else?
            <a href="{{ route('pages.contact') }}" class="text-indigo-600 hover:underline">Contact us</a>
            — we read and respond to every message.
        </p>
    </div>
</div>
@endsection

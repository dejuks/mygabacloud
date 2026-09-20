@extends('layouts.app')
@section('title', 'Terms of Service')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-2">Terms of Service</h1>
    <p class="text-sm text-slate-400 mb-8">Last updated: {{ now()->format('F j, Y') }}</p>

    <div class="prose prose-slate max-w-none space-y-6 text-slate-700">
        <p>
            These Terms of Service ("Terms") govern your use of {{ config('app.name') }}. By creating
            an account or using this site, you agree to these Terms. If you don't agree, please don't
            use the site.
        </p>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">1. Accounts</h2>
            <p>
                You must provide accurate information when registering and keep your login credentials
                secure. You're responsible for all activity under your account. We may suspend or
                terminate accounts that violate these Terms.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">2. Buying products</h2>
            <p>
                When you purchase a product, you receive a license to use it under the terms shown on
                that product's page (Regular or Extended license) — not ownership of the underlying
                work. Downloads and license keys are available in your library immediately after a
                completed purchase.
            </p>
            <p>
                Digital products are, by their nature, generally non-refundable once downloaded — a
                buyer who has the files has received what they paid for. If a product is materially
                different from its listing, doesn't work as described, or you believe you were
                charged in error, <a href="{{ route('pages.contact') }}" class="text-indigo-600 hover:underline">contact us</a>
                and we'll review it case by case.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">3. Selling products</h2>
            <p>
                Sellers must be approved before listing products, and each product is reviewed before
                it goes live. By submitting a product, you confirm that:
            </p>
            <ul class="list-disc list-inside space-y-1">
                <li>You own the rights to the content, or have the right to distribute and sell it</li>
                <li>It doesn't infringe anyone else's copyright, trademark, or other rights</li>
                <li>It doesn't contain malware, hidden functionality, or anything designed to harm a buyer</li>
                <li>The listing accurately describes what a buyer will receive</li>
            </ul>
            <p>
                We charge a commission on each sale, shown in your seller dashboard, which may vary by
                seller or promotion. Commission rates in effect at the time of a sale apply to that
                sale — a later rate change never retroactively affects past sales.
            </p>
            <p>
                Seller earnings are held for a short period after each sale before becoming available
                for payout — this protects against chargebacks and disputed payments. We may revoke
                access and reverse a seller's earnings on a specific sale if it's later found to be
                fraudulent.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">4. Free access via engagement</h2>
            <p>
                Some products may be available for free in exchange for completing steps a seller sets
                (e.g. watching a video, subscribing to a channel). This is entirely at the seller's
                discretion per product, is reviewed manually before access is granted, and may be
                revoked if the submitted information turns out to be false.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">5. Prohibited conduct</h2>
            <p>You agree not to:</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Upload or sell content you don't have the rights to</li>
                <li>Upload malware, or anything designed to damage or exploit a buyer's systems</li>
                <li>Attempt to circumvent payment, licensing, or download-access controls</li>
                <li>Submit false information in a payment verification or free-access request</li>
                <li>Use the site for any unlawful purpose</li>
            </ul>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">6. Payments</h2>
            <p>
                Card and PayPal payments are processed automatically. Some payment methods (bank
                transfer, mobile money, certain cryptocurrency transfers) are verified manually — your
                order is completed once that verification is approved, generally within a few hours.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">7. Disclaimer & limitation of liability</h2>
            <p>
                Products are provided by independent sellers "as is." We review listings before
                publication but don't independently verify every claim a seller makes. To the maximum
                extent permitted by law, {{ config('app.name') }} is not liable for indirect,
                incidental, or consequential damages arising from your use of a product purchased
                through this site.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">8. Changes to these Terms</h2>
            <p>
                We may update these Terms from time to time. Continued use of the site after a change
                means you accept the updated Terms.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">9. Contact us</h2>
            <p>
                Questions about these Terms? <a href="{{ route('pages.contact') }}" class="text-indigo-600 hover:underline">Contact us</a>.
            </p>
        </div>
    </div>
</div>
@endsection

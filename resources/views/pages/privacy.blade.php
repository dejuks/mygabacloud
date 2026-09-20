@extends('layouts.app')
@section('title', 'Privacy Policy')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-2">Privacy Policy</h1>
    <p class="text-sm text-slate-400 mb-8">Last updated: {{ now()->format('F j, Y') }}</p>

    <div class="prose prose-slate max-w-none space-y-6 text-slate-700">
        <p>
            This Privacy Policy explains what information {{ config('app.name') }} ("we", "us")
            collects when you use this site, how we use it, and the choices you have. By using
            {{ config('app.name') }}, you agree to the collection and use of information as
            described here.
        </p>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Information we collect</h2>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>Account information:</strong> name, email address, and password (stored
                    encrypted, never in plain text) when you register.</li>
                <li><strong>Seller information:</strong> store name, description, and payout details
                    (PayPal email, or bank/mobile-money details for manual payout methods) if you
                    apply to sell.</li>
                <li><strong>Order information:</strong> products purchased, license keys issued, and
                    payment method used. We do not store full card numbers — card payments are
                    processed directly by Stripe, and we only receive confirmation that payment
                    succeeded.</li>
                <li><strong>Uploaded content:</strong> product files, preview images, and — for
                    payment verification or free-access requests — screenshots you submit as proof.</li>
                <li><strong>Usage data:</strong> pages visited, products viewed, and similar
                    analytics collected automatically as you browse.</li>
                <li><strong>Cookies:</strong> see the dedicated section below.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">How we use this information</h2>
            <p>We use the information we collect to:</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Create and maintain your account, and process purchases and payouts</li>
                <li>Deliver license keys and product downloads to buyers</li>
                <li>Verify manual payments and free-access requests (see the relevant checkout/product
                    pages for how these are reviewed)</li>
                <li>Send order confirmations, sale notifications, and account-related emails</li>
                <li>Detect and prevent fraud, abuse, and violations of our
                    <a href="{{ route('pages.terms') }}" class="text-indigo-600 hover:underline">Terms of Service</a></li>
                <li>Improve the site and understand how it's used</li>
                <li>Show relevant advertising, where applicable (see below)</li>
            </ul>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Cookies and advertising</h2>
            <p>
                We use cookies and similar technologies to keep you logged in, remember your cart, and
                understand how the site is used.
            </p>
            <p>
                This site may display advertisements served by Google AdSense and other third-party
                advertising networks. These networks may use cookies, web beacons, or similar
                technologies to serve ads based on your prior visits to this site or other websites,
                and to measure ad performance. Google's use of advertising cookies enables it and its
                partners to serve ads based on your visit to this site and/or other sites on the
                internet.
            </p>
            <p>
                You can opt out of personalized advertising by visiting
                <a href="https://adssettings.google.com" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">Google's Ads Settings</a>,
                or generally at
                <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">aboutads.info</a>.
                Most browsers also let you refuse or delete cookies through their settings — doing so
                may affect how parts of this site function (e.g. staying logged in, keeping items in
                your cart).
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Third parties we share data with</h2>
            <p>We share the minimum information necessary with:</p>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>Payment processors</strong> (Stripe, PayPal, and equivalents for manual
                    payment methods) — to process payments and payouts</li>
                <li><strong>Email delivery services</strong> — to send account and order-related
                    emails</li>
                <li><strong>Advertising networks</strong> (e.g. Google AdSense), if enabled — as
                    described above</li>
                <li><strong>Law enforcement or regulators</strong>, only where legally required</li>
            </ul>
            <p>We do not sell your personal information.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Data retention</h2>
            <p>
                We retain account and order data for as long as your account is active and as needed
                to comply with legal, tax, and accounting obligations. Uploaded proof screenshots
                (payment verification, free-access requests) are retained for audit purposes.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Your rights</h2>
            <p>
                You can review and update your account information at any time while logged in. To
                request deletion of your account or data, or if you have any other privacy question,
                <a href="{{ route('pages.contact') }}" class="text-indigo-600 hover:underline">contact us</a>.
                Depending on your location, you may have additional rights under applicable data
                protection law (such as the GDPR or CCPA), including the right to access, correct, or
                delete your personal data.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Children's privacy</h2>
            <p>
                This site is not directed at children under 13, and we do not knowingly collect
                personal information from children under 13.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Changes to this policy</h2>
            <p>
                We may update this Privacy Policy from time to time. Material changes will be
                reflected by an updated "Last updated" date above.
            </p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Contact us</h2>
            <p>
                Questions about this policy? <a href="{{ route('pages.contact') }}" class="text-indigo-600 hover:underline">Contact us</a>.
            </p>
        </div>
    </div>
</div>
@endsection

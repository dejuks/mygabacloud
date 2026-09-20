# Add PayPal + Stripe config to config/services.php

Inside the array returned by `config/services.php`, add:

```php
'stripe' => [
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],

'paypal' => [
    'mode' => env('PAYPAL_MODE', 'sandbox'),      // 'sandbox' or 'live'
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    'webhook_id' => env('PAYPAL_WEBHOOK_ID'),      // from your PayPal app's webhook settings
],
```

And in `.env`:

```ini
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_WEBHOOK_ID=...
```

## Getting PayPal sandbox credentials

1. Go to https://developer.paypal.com/dashboard/applications/sandbox
2. Create an app (or use the Default Application) — this gives you the client ID and secret
3. Under the app's **Webhooks** section, add a webhook pointed at
   `https://your-domain.test/webhooks/paypal` subscribed to at minimum:
   - `PAYMENT.CAPTURE.COMPLETED`
   - `PAYMENT.CAPTURE.REFUNDED`
4. Copy the Webhook ID it gives you into `PAYPAL_WEBHOOK_ID`

For local development, use a tunnel (ngrok, Expose, Cloudflare Tunnel) so
PayPal's servers can reach your webhook endpoint.

## Optional: AI-assisted content review (product upload screening)

Add to `config/services.php`:

```php
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'model' => env('ANTHROPIC_MODEL', 'claude-3-5-haiku-20241022'), // cheap/fast is fine for this
],
```

And in `.env`:

```ini
ANTHROPIC_API_KEY=sk-ant-...
```

This is entirely optional — `AiContentReviewer` checks for this key and simply
returns nothing if it's not set, so leaving it blank means the AI-advisory
line just never shows up on a reviewed file. No other feature depends on it.

Get a key from https://console.anthropic.com/settings/keys — note this is a
paid API (billed per request, quite cheap for short text excerpts on Haiku),
separate from any Claude.ai subscription.

## Optional: PDF text extraction (needed for AI review of PDF uploads)

Without this package, integrity checking and duplicate detection still work
fully for PDFs — only the "extract some text and ask AI to look at it" step
is skipped for PDFs specifically (DOCX/PPTX/XLSX text extraction doesn't need
this, since those formats are just ZIP+XML under the hood).

```bash
composer require smalot/pdfparser
```

## Optional: Google AdSense

Add to `config/services.php`:

```php
'adsense' => [
    'publisher_id' => env('ADSENSE_PUBLISHER_ID'), // e.g. ca-pub-0000000000000000
],
```

And in `.env`, once you have a real AdSense account:

```ini
ADSENSE_PUBLISHER_ID=ca-pub-0000000000000000
```

Leaving this unset means no AdSense script or verification meta tag renders
at all — nothing breaks, the site just doesn't have ads configured yet.
Once you have your publisher ID, also update `public/ads.txt` with the exact
line Google gives you in your AdSense dashboard.



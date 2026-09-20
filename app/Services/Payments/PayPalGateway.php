<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Orders v2 integration using Laravel's Http client directly — no
 * extra package required. Requires in .env:
 *
 *   PAYPAL_MODE=sandbox            # or 'live'
 *   PAYPAL_CLIENT_ID=...
 *   PAYPAL_CLIENT_SECRET=...
 *   PAYPAL_WEBHOOK_ID=...          # from your PayPal app's webhook settings
 *
 * Flow: createCheckout() opens a PayPal order and returns the approval link.
 * The buyer approves on PayPal's site and is redirected back to
 * checkout.paypal.capture, which captures the payment synchronously. The
 * webhook is kept as an idempotent backstop for the same event.
 */
class PayPalGateway implements PaymentGateway
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function createCheckout(Order $order): ?string
    {
        $response = $this->client()->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $order->id,
                'custom_id' => (string) $order->id,
                'amount' => [
                    'currency_code' => $order->currency,
                    'value' => number_format((float) $order->grand_total, 2, '.', ''),
                ],
                'description' => 'Order ' . $order->order_number,
            ]],
            'application_context' => [
                'return_url' => route('checkout.paypal.capture', $order),
                'cancel_url' => route('checkout.index'),
                'user_action' => 'PAY_NOW',
                'brand_name' => config('app.name'),
            ],
        ])->throw()->json();

        $order->update(['gateway_reference' => $response['id']]);

        $approveLink = collect($response['links'] ?? [])->firstWhere('rel', 'approve');

        return $approveLink['href'] ?? null;
    }

    /**
     * Captures an approved PayPal order. Called from the return URL the
     * buyer lands on after approving payment on PayPal's site.
     *
     * @return array{success: bool, capture_id: ?string, raw: array}
     */
    public function capture(Order $order): array
    {
        $response = $this->client()
            ->post("/v2/checkout/orders/{$order->gateway_reference}/capture")
            ->json();

        $status = $response['status'] ?? null;
        $captureId = $response['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

        if ($status !== 'COMPLETED' || ! $captureId) {
            Log::warning("PayPal capture did not complete for order {$order->id}", $response);
            return ['success' => false, 'capture_id' => null, 'raw' => $response];
        }

        return ['success' => true, 'capture_id' => $captureId, 'raw' => $response];
    }

    /**
     * Verifies an incoming webhook actually came from PayPal, using their
     * server-side verification endpoint. Do not trust a webhook body without
     * this — anyone can POST a fake "payment completed" event otherwise.
     */
    public function verifyWebhookSignature(array $headers, array $body): bool
    {
        $payload = [
            'auth_algo' => $headers['paypal-auth-algo'][0] ?? null,
            'cert_url' => $headers['paypal-cert-url'][0] ?? null,
            'transmission_id' => $headers['paypal-transmission-id'][0] ?? null,
            'transmission_sig' => $headers['paypal-transmission-sig'][0] ?? null,
            'transmission_time' => $headers['paypal-transmission-time'][0] ?? null,
            'webhook_id' => config('services.paypal.webhook_id'),
            'webhook_event' => $body,
        ];

        if (in_array(null, $payload, true)) {
            return false;
        }

        $response = $this->client()
            ->post('/v1/notifications/verify-webhook-signature', $payload)
            ->json();

        return ($response['verification_status'] ?? null) === 'SUCCESS';
    }

    protected function client()
    {
        return Http::withToken($this->accessToken())->baseUrl($this->baseUrl);
    }

    protected function accessToken(): string
    {
        return cache()->remember('paypal_access_token', 3000, function () {
            $response = Http::asForm()
                ->withBasicAuth(
                    config('services.paypal.client_id'),
                    config('services.paypal.client_secret')
                )
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ])
                ->throw()
                ->json();

            return $response['access_token'];
        });
    }
}

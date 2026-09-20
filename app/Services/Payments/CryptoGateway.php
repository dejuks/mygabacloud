<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * USDT payments over TRC20 (Tron) or BEP20 (BNB Smart Chain), via NOWPayments'
 * hosted invoice API — https://api.nowpayments.io/v1/invoice.
 *
 * Requires in .env:
 *   NOWPAYMENTS_API_KEY=...
 *   NOWPAYMENTS_IPN_SECRET=...     (separate from the API key — from
 *                                    Dashboard > Store Settings > IPN Secret)
 *   NOWPAYMENTS_SANDBOX=true       (false in production)
 *
 * We do NOT generate or hold any wallet address or private key ourselves —
 * NOWPayments custodies the deposit address and settlement. This keeps the
 * marketplace out of the business of blockchain key management, which is a
 * materially different risk profile than card/PayPal processing.
 */
class CryptoGateway implements PaymentGateway
{
    /**
     * NOWPayments' currency ticker for each network we offer. USDT BEP20 is
     * ticker 'usdtbsc' in their API (BSC = BNB Smart Chain); 'usdtbep20' is
     * accepted by some integrations as an alias but 'usdtbsc' is canonical.
     */
    protected const NETWORK_TICKERS = [
        'usdt_trc20' => 'usdttrc20',
        'usdt_bep20' => 'usdtbsc',
    ];

    protected string $baseUrl;

    public function __construct(protected string $network)
    {
        if (! isset(self::NETWORK_TICKERS[$network])) {
            throw new \InvalidArgumentException("Unsupported crypto network: {$network}");
        }

        $this->baseUrl = config('services.nowpayments.sandbox')
            ? 'https://api-sandbox.nowpayments.io'
            : 'https://api.nowpayments.io';
    }

    public function createCheckout(Order $order): ?string
    {
        $response = Http::withHeaders(['x-api-key' => config('services.nowpayments.api_key')])
            ->post("{$this->baseUrl}/v1/invoice", [
                'price_amount' => (float) $order->grand_total,
                'price_currency' => strtolower($order->currency),
                'pay_currency' => self::NETWORK_TICKERS[$this->network],
                'order_id' => (string) $order->id,
                'order_description' => 'Order ' . $order->order_number,
                'ipn_callback_url' => route('webhooks.nowpayments'),
                'success_url' => route('checkout.success', $order),
                'cancel_url' => route('checkout.index'),
            ]);

        if ($response->failed()) {
            Log::error('NOWPayments invoice creation failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $data = $response->json();

        $order->update(['gateway_reference' => (string) $data['id']]);

        return $data['invoice_url'] ?? null;
    }

    /**
     * Verifies an IPN callback body against NOWPayments' documented signature
     * scheme: sort the body alphabetically by key, JSON-encode it, HMAC-SHA512
     * with the IPN secret, compare hex digest to the x-nowpayments-sig header.
     * https://documenter.getpostman.com/view/7907941/2s93JusNJt (IPN section)
     */
    public static function verifySignature(array $body, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $secret = config('services.nowpayments.ipn_secret');

        if (! $secret) {
            Log::error('NOWPAYMENTS_IPN_SECRET is not configured — refusing to trust webhook.');
            return false;
        }

        $sorted = $body;
        ksort($sorted);

        // UNESCAPED_SLASHES to match JavaScript's JSON.stringify, which the
        // official NOWPayments examples use to build the signed string —
        // PHP escapes forward slashes by default and would produce a
        // different byte string (and therefore a different HMAC) otherwise.
        $json = json_encode($sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $expected = hash_hmac('sha512', $json, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * NOWPayments payment_status values that mean "money has actually
     * settled" — anything else (waiting, confirming, partially_paid,
     * sending) is still in flight and must not fulfil the order.
     */
    public static function isSettled(string $paymentStatus): bool
    {
        return $paymentStatus === 'finished';
    }
}

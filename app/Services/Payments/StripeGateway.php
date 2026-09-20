<?php

namespace App\Services\Payments;

use App\Models\Order;
use Stripe\StripeClient;

/**
 * Requires: composer require stripe/stripe-php
 * and STRIPE_SECRET / STRIPE_WEBHOOK_SECRET in .env
 */
class StripeGateway implements PaymentGateway
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckout(Order $order): ?string
    {
        $lineItems = $order->items->isNotEmpty()
            ? $order->items
            : collect();

        // The order isn't itemised until payment succeeds, so bill the grand
        // total as a single line and carry the order id in metadata.
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => (string) $order->id,
            'customer_email' => $order->buyer->email,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => (int) round($order->grand_total * 100),
                    'product_data' => [
                        'name' => 'Order ' . $order->order_number,
                        'description' => $order->items_summary ?? 'Digital products',
                    ],
                ],
            ]],
            'success_url' => route('checkout.success', $order) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.index'),
        ]);

        $order->update(['gateway_reference' => $session->id]);

        return $session->url;
    }
}

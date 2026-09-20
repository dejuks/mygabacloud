<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderFulfilmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(protected OrderFulfilmentService $fulfilment)
    {
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            // Verifies the request really came from Stripe. Without this,
            // anyone could POST a fake "payment succeeded" and steal products.
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCompleted($event),
            'charge.refunded' => $this->handleRefund($event),
            default => Log::info("Unhandled Stripe event: {$event->type}"),
        };

        return response()->json(['received' => true]);
    }

    protected function handleCompleted($event): void
    {
        $session = $event->data->object;
        $orderId = $session->metadata->order_id ?? $session->client_reference_id;

        $order = Order::find($orderId);

        if (! $order) {
            Log::error("Stripe webhook: order {$orderId} not found");
            return;
        }

        // Rebuild the line items from the snapshot stored at checkout time.
        // The session is gone by now, so we persist it on the order instead.
        $snapshot = $order->cart_snapshot ?? [];

        if (empty($snapshot)) {
            Log::error("Stripe webhook: no cart snapshot for order {$order->id}");
            return;
        }

        $items = collect($snapshot)->map(fn ($row) => [
            'product' => Product::find($row['product_id']),
            'license_type' => $row['license_type'],
            'price' => $row['price'],
        ])->filter(fn ($row) => $row['product'] !== null)->all();

        $this->fulfilment->fulfil(
            $order,
            $items,
            $event->id,
            ['session_id' => $session->id]
        );
    }

    protected function handleRefund($event): void
    {
        $charge = $event->data->object;
        $order = Order::where('gateway_reference', $charge->payment_intent)->first();

        if (! $order) {
            return;
        }

        foreach ($order->items as $item) {
            $this->fulfilment->refund($item, $event->id . '_' . $item->id);
        }

        $order->update(['status' => 'refunded']);
    }
}

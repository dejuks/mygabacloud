<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderFulfilmentService;
use App\Services\Payments\PayPalGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    public function __construct(protected OrderFulfilmentService $fulfilment)
    {
    }

    public function handle(Request $request)
    {
        $body = $request->json()->all();

        $gateway = new PayPalGateway();

        if (! $gateway->verifyWebhookSignature($request->headers->all(), $body)) {
            Log::warning('PayPal webhook signature verification failed.');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        match ($body['event_type'] ?? null) {
            'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($body),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handleRefund($body),
            default => Log::info('Unhandled PayPal event: ' . ($body['event_type'] ?? 'unknown')),
        };

        return response()->json(['received' => true]);
    }

    /**
     * Normally the order is already fulfilled synchronously in
     * CheckoutController::capturePaypal(). This exists as a backstop for the
     * case where the buyer's browser closes before the redirect completes —
     * fulfil() is idempotent on gateway_event_id, so re-running here is safe.
     */
    protected function handleCaptureCompleted(array $body): void
    {
        $resource = $body['resource'] ?? [];
        $captureId = $resource['id'] ?? null;
        $customId = $resource['custom_id']
            ?? $resource['supplementary_data']['related_ids']['order_id']
            ?? null;

        $order = Order::find($customId);

        if (! $order || ! $captureId) {
            Log::warning('PayPal webhook: could not resolve order from capture event.');
            return;
        }

        $snapshot = $order->cart_snapshot ?? [];

        $items = collect($snapshot)->map(fn ($row) => [
            'product' => Product::find($row['product_id']),
            'license_type' => $row['license_type'],
            'price' => $row['price'],
        ])->filter(fn ($row) => $row['product'] !== null)->all();

        if (empty($items)) {
            return;
        }

        $this->fulfilment->fulfil($order, $items, 'paypal_' . $captureId, $body);
    }

    protected function handleRefund(array $body): void
    {
        $resource = $body['resource'] ?? [];
        $captureId = $resource['links'][0]['href'] ?? null;

        // PayPal refund events reference the original capture, not our order
        // id directly — look it up via the transaction we stored at capture time.
        $order = Order::whereHas('transactions', function ($q) use ($resource) {
            $q->where('gateway', 'paypal')
              ->where('gateway_event_id', 'like', '%' . ($resource['id'] ?? '__none__') . '%');
        })->first();

        if (! $order) {
            Log::warning('PayPal refund webhook: could not resolve order.');
            return;
        }

        foreach ($order->items as $item) {
            $this->fulfilment->refund($item, 'paypal_refund_' . ($resource['id'] ?? uniqid()) . '_' . $item->id);
        }

        $order->update(['status' => 'refunded']);
    }
}

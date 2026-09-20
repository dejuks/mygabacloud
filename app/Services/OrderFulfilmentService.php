<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\SellerWallet;
use App\Models\Transaction;
use App\Notifications\NewSaleNotification;
use App\Notifications\PurchaseCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a *paid* order into order items, licenses and seller wallet credits.
 *
 * This is the single place where money is split. It is called from payment
 * webhooks, so it MUST be idempotent — gateways retry webhooks, and a double
 * run would pay sellers twice.
 */
class OrderFulfilmentService
{
    /**
     * @param  array<int, array{product: Product, license_type: string, price: float}>  $items
     */
    public function fulfil(Order $order, array $items, string $gatewayEventId, array $rawPayload = []): Order
    {
        // Idempotency guard: if we already logged this gateway event, stop.
        if (Transaction::where('gateway_event_id', $gatewayEventId)->exists()) {
            Log::info("Duplicate webhook ignored: {$gatewayEventId}");
            return $order->fresh();
        }

        // A second guard on the order itself, in case two different event ids
        // both resolve to the same order (e.g. Stripe + a manual retry).
        if ($order->status === 'paid') {
            Log::info("Order {$order->id} already fulfilled.");
            return $order;
        }

        return DB::transaction(function () use ($order, $items, $gatewayEventId, $rawPayload) {

            foreach ($items as $row) {
                $product = $row['product'];
                $price = (float) $row['price'];
                $licenseType = $row['license_type'];

                // Read the seller's rate NOW and snapshot it onto the line item.
                // Changing the rate later must never alter this sale.
                $rate = $product->seller->sellerProfile?->commissionRate()
                    ?? (float) PlatformSetting::get('default_commission_rate', 30);

                $split = OrderItem::calculateSplit($price, $rate);

                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'license_type' => $licenseType,
                    'price' => $price,
                    'commission_rate' => $rate,
                    'commission_amount' => $split['commissionAmount'],
                    'seller_earning' => $split['sellerEarning'],
                    'status' => 'completed',
                ]);

                // Issue the buyer's license (key is auto-generated in the model)
                License::create([
                    'order_item_id' => $orderItem->id,
                    'buyer_id' => $order->buyer_id,
                    'product_id' => $product->id,
                    'type' => $licenseType,
                ]);

                // Credit the seller. firstOrCreate covers sellers whose wallet
                // row was never initialised.
                $wallet = SellerWallet::firstOrCreate(
                    ['seller_id' => $product->seller_id],
                    ['available_balance' => 0, 'pending_balance' => 0]
                );
                $wallet->creditSale($orderItem);

                // Denormalised counters
                $product->increment('sales_count');
                $product->seller->sellerProfile?->increment('total_sales');

                // Tell the seller they made a sale
                \App\Support\SafeNotifier::send($product->seller, new NewSaleNotification($orderItem));
            }

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            Transaction::create([
                'order_id' => $order->id,
                'gateway' => $order->payment_method,
                'gateway_event_id' => $gatewayEventId,
                'type' => 'payment',
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'status' => 'succeeded',
                'raw_payload' => $rawPayload,
            ]);

            if ($order->coupon) {
                $order->coupon->increment('used_count');
            }

            \App\Support\SafeNotifier::send($order->buyer, new PurchaseCompleted($order));

            \App\Support\ActivityLogger::log(
                'order.completed',
                $order,
                "{$order->buyer->name} completed order {$order->order_number} (\${$order->grand_total}, {$order->payment_method}).",
                forUserId: $order->buyer_id
            );

            return $order->fresh(['items.product', 'items.license']);
        });
    }

    /**
     * Reverse a sale. Debits the seller's wallet and revokes the license.
     */
    public function refund(OrderItem $item, string $gatewayEventId): void
    {
        if ($item->status === 'refunded') {
            return;
        }

        DB::transaction(function () use ($item, $gatewayEventId) {
            $wallet = SellerWallet::where('seller_id', $item->seller_id)->lockForUpdate()->first();

            if ($wallet) {
                // Pull the money back out of whichever bucket still holds it
                if ($wallet->pending_balance >= $item->seller_earning) {
                    $wallet->decrement('pending_balance', $item->seller_earning);
                } else {
                    $wallet->decrement('available_balance', $item->seller_earning);
                }

                $wallet->ledgerEntries()->create([
                    'type' => 'refund_debit',
                    'amount' => -$item->seller_earning,
                    'reference_type' => OrderItem::class,
                    'reference_id' => $item->id,
                    'note' => "Refund of order item #{$item->id}",
                ]);
            }

            $item->update(['status' => 'refunded']);
            $item->license?->update(['status' => 'revoked']);
            $item->product->decrement('sales_count');

            Transaction::create([
                'order_id' => $item->order_id,
                'gateway' => $item->order->payment_method,
                'gateway_event_id' => $gatewayEventId,
                'type' => 'refund',
                'amount' => $item->price,
                'currency' => $item->order->currency,
                'status' => 'succeeded',
            ]);
        });
    }
}

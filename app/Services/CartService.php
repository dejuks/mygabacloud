<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Session-backed cart. Stores only product_id + license_type; prices are always
 * re-read from the database at render/checkout time so a stale session can never
 * be used to buy at an old price.
 */
class CartService
{
    protected const KEY = 'cart';
    protected const COUPON_KEY = 'cart_coupon';

    public function add(Product $product, string $licenseType = 'regular'): void
    {
        $cart = session()->get(self::KEY, []);

        // Digital goods: quantity is always 1. Re-adding just switches the license.
        $cart[$product->id] = ['license_type' => $licenseType];

        session()->put(self::KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = session()->get(self::KEY, []);
        unset($cart[$productId]);
        session()->put(self::KEY, $cart);
    }

    public function clear(): void
    {
        session()->forget([self::KEY, self::COUPON_KEY]);
    }

    public function has(int $productId): bool
    {
        return array_key_exists($productId, session()->get(self::KEY, []));
    }

    public function isEmpty(): bool
    {
        return empty(session()->get(self::KEY, []));
    }

    /**
     * @return Collection<int, array{product: Product, license_type: string, price: float}>
     */
    public function items(): Collection
    {
        $cart = session()->get(self::KEY, []);

        if (empty($cart)) {
            return collect();
        }

        $products = Product::published()
            ->with('seller')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->filter(fn ($row, $id) => $products->has($id))
            ->map(function ($row, $id) use ($products) {
                $product = $products[$id];
                $type = $row['license_type'] ?? 'regular';

                // Fall back to regular if the seller never set an extended price
                if ($type === 'extended' && ! $product->extended_price) {
                    $type = 'regular';
                }

                return [
                    'product' => $product,
                    'license_type' => $type,
                    'price' => $product->priceFor($type),
                ];
            })
            ->values();
    }

    public function subtotal(): float
    {
        return round((float) $this->items()->sum('price'), 2);
    }

    public function applyCoupon(Coupon $coupon): void
    {
        session()->put(self::COUPON_KEY, $coupon->id);
    }

    public function removeCoupon(): void
    {
        session()->forget(self::COUPON_KEY);
    }

    public function coupon(): ?Coupon
    {
        $id = session()->get(self::COUPON_KEY);

        if (! $id) {
            return null;
        }

        $coupon = Coupon::find($id);

        // Silently drop a coupon that expired while sitting in the session
        if (! $coupon || ! $coupon->isValid()) {
            $this->removeCoupon();
            return null;
        }

        return $coupon;
    }

    public function discount(): float
    {
        $coupon = $this->coupon();

        return $coupon ? $coupon->discountFor($this->subtotal()) : 0.0;
    }

    public function total(): float
    {
        return round(max(0, $this->subtotal() - $this->discount()), 2);
    }

    public function count(): int
    {
        return count(session()->get(self::KEY, []));
    }
}

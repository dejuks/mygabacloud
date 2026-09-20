<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * Development-only gateway. Completes the payment immediately by redirecting
 * to the simulated-success route, which invokes the real fulfilment service.
 *
 * NEVER enable this in production — set PAYMENT_DRIVER=stripe there.
 */
class TestGateway implements PaymentGateway
{
    public function createCheckout(Order $order): ?string
    {
        return route('checkout.test.complete', $order);
    }
}

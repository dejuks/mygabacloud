<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    /**
     * Start a payment. Returns a URL the buyer should be redirected to,
     * or null when the gateway completes inline (test driver).
     */
    public function createCheckout(Order $order): ?string;
}

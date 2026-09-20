<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // e.g. ORD-20260918-XXXXX
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->string('currency', 3)->default('USD');
            // No FK constraint here — the coupons table is created later
            // (migration 000013). The constraint itself is added in
            // 2024_01_01_000016_add_coupon_foreign_key_to_orders_table.
            $table->unsignedBigInteger('coupon_id')->nullable();

            $table->enum('payment_method', ['stripe', 'paypal']);
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'])
                  ->default('pending');
            $table->string('gateway_reference')->nullable(); // Stripe PaymentIntent ID / PayPal order id
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

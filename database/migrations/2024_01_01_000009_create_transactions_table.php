<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('gateway', ['stripe', 'paypal']);
            $table->string('gateway_event_id')->unique(); // webhook event id -> idempotency guard
            $table->string('type'); // payment, refund, payout
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['succeeded', 'failed', 'pending']);
            $table->json('raw_payload')->nullable(); // full webhook payload for auditing
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

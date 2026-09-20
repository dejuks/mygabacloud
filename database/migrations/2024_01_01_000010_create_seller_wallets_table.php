<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('available_balance', 10, 2)->default(0); // can be withdrawn now
            $table->decimal('pending_balance', 10, 2)->default(0);   // in holding period (chargeback window)
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_withdrawn', 12, 2)->default(0);
            $table->timestamps();
        });

        // Immutable ledger — every credit/debit to a wallet, for auditability
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sale_credit', 'refund_debit', 'payout_debit', 'adjustment']);
            $table->decimal('amount', 10, 2); // positive = credit, negative = debit
            $table->nullableMorphs('reference'); // polymorphic: order_item, payout_request, etc.
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger_entries');
        Schema::dropIfExists('seller_wallets');
    }
};

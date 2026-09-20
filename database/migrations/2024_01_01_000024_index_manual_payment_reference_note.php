<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Not a uniqueness constraint (a genuinely reused CBE/Telebirr receipt
     * screenshot for two different orders is a real fraud pattern worth
     * flagging to the admin, but blocking it outright would also break the
     * legitimate case of a buyer fat-fingering the same note twice) — this
     * is just an index so ManualPaymentController can cheaply check "has
     * this reference been used before?" on every review-queue page load.
     */
    public function up(): void
    {
        Schema::table('manual_payment_proofs', function (Blueprint $table) {
            $table->index(['method', 'reference_note']);
        });
    }

    public function down(): void
    {
        Schema::table('manual_payment_proofs', function (Blueprint $table) {
            $table->dropIndex(['method', 'reference_note']);
        });
    }
};

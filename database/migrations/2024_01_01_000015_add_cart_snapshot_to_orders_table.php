<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Line items are snapshotted here at checkout so fulfilment (which
            // runs later, from a webhook, with no session) knows exactly what
            // was bought and at what price.
            $table->json('cart_snapshot')->nullable()->after('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('cart_snapshot');
        });
    }
};

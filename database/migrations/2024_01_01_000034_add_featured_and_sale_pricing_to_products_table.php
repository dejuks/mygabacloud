<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Admin-curated homepage feature — deliberately NOT settable by
            // sellers themselves (see Product::isFeaturedSettable logic in
            // the controller), unlike sale_price/sale_ends_at below.
            $table->boolean('is_featured')->default(false)->after('status');

            // A deal/sale on the REGULAR license price. When set and not
            // expired, this is the real price charged at checkout — not
            // just a cosmetic badge. See Product::priceFor().
            $table->decimal('sale_price', 10, 2)->nullable()->after('regular_price');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'sale_price', 'sale_ends_at']);
        });
    }
};

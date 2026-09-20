<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Seller opt-in: when true, buyers can request free access by
            // completing YouTube engagement (watch/subscribe/like/comment/share)
            // instead of paying, subject to manual admin approval.
            $table->boolean('allow_free_unlock')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('allow_free_unlock');
        });
    }
};

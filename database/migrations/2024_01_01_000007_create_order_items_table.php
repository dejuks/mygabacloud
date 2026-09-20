<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();

            $table->enum('license_type', ['regular', 'extended']);
            $table->decimal('price', 10, 2);
            // Snapshot the commission math at time of sale — never recompute retroactively
            $table->decimal('commission_rate', 5, 2);      // % applied at time of sale
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('seller_earning', 10, 2);

            $table->enum('status', ['completed', 'refunded'])->default('completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('store_name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->enum('application_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_verified')->default(false); // ID/KYC verified
            $table->decimal('custom_commission_rate', 5, 2)->nullable(); // overrides global rate if set
            $table->unsignedInteger('total_sales')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};

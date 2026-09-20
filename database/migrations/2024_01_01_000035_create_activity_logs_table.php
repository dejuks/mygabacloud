<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Null user_id = system/automated action (e.g. a scheduled job),
            // not a deleted user — the FK is nullOnDelete so a deleted
            // account's history stays readable instead of vanishing.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // e.g. 'product.approved', 'seller.suspended'
            $table->nullableMorphs('subject'); // what was acted on, if anything
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

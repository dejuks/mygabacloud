<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagement_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // What the buyer says they did: watched, subscribed, liked,
            // commented, shared — free text + a link, verified by eye by an
            // admin (there is no API that proves a YouTube subscribe/like
            // happened, so this is inherently a manual, honor-system step).
            $table->string('proof_url')->nullable();   // link to their comment/share post, if any
            $table->text('proof_note');                 // what they did + channel/handle used
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_unlocks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description');
            $table->string('thumbnail')->nullable();

            // Pricing — regular vs extended license (Envato/Codester style)
            $table->decimal('regular_price', 10, 2);
            $table->decimal('extended_price', 10, 2)->nullable();

            // Metadata
            $table->string('demo_url')->nullable();
            $table->string('framework')->nullable();      // e.g. Laravel, React, WordPress
            $table->string('current_version')->default('1.0.0');
            $table->json('compatible_with')->nullable();  // e.g. ["PHP 8.2", "MySQL 8"]

            // Workflow / moderation
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'suspended'])
                  ->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // Stats (denormalized for performance)
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'category_id']);
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'tag_id']);
        });

        Schema::create('product_changelogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->text('notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_changelogs');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('products');
    }
};

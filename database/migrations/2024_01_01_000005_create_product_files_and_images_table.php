<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The actual sellable source files — stored on a PRIVATE disk, never public
        Schema::create('product_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('private'); // e.g. s3-private
            $table->string('path');                      // storage path, never exposed raw
            $table->string('original_name');
            $table->unsignedBigInteger('size_bytes');
            $table->string('version')->default('1.0.0');
            $table->string('checksum')->nullable();       // sha256, for integrity verification
            $table->enum('scan_status', ['pending', 'clean', 'infected', 'failed'])->default('pending');
            $table->timestamps();
        });

        // Preview / gallery images — public disk
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_files');
    }
};

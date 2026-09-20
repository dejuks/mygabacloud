<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('engagement_unlocks', function (Blueprint $table) {
            // Shown next to the thumbnail in the admin queue so it's obvious
            // exactly which file was uploaded — removes any ambiguity about
            // whether the right screenshot came through.
            $table->string('proof_screenshot_original_name')->nullable()->after('proof_screenshot_path');
        });
    }

    public function down(): void
    {
        Schema::table('engagement_unlocks', function (Blueprint $table) {
            $table->dropColumn('proof_screenshot_original_name');
        });
    }
};

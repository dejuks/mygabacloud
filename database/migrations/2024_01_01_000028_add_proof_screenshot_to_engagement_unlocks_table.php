<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('engagement_unlocks', function (Blueprint $table) {
            // A single screenshot showing the subscribe + like + comment
            // together, so the admin has one thing to look at instead of
            // trusting four separate self-reported checkboxes blind.
            $table->string('proof_screenshot_path')->nullable()->after('proof_url');
        });
    }

    public function down(): void
    {
        Schema::table('engagement_unlocks', function (Blueprint $table) {
            $table->dropColumn('proof_screenshot_path');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('engagement_unlocks', function (Blueprint $table) {
            // Reported by the buyer's browser via the YouTube Player API.
            // Shown to the admin for context ("watched 240s of a 240s
            // requirement") — NOT a server-verified value, since nothing
            // stops a motivated buyer from editing the hidden form field
            // before submitting. The real gate is still admin judgment,
            // same as the rest of this feature.
            $table->unsignedInteger('watched_seconds')->nullable()->after('shared');
        });
    }

    public function down(): void
    {
        Schema::table('engagement_unlocks', function (Blueprint $table) {
            $table->dropColumn('watched_seconds');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Minimum seconds the buyer must actively watch the linked video
            // before the subscribe/like/comment/share checklist becomes
            // interactive. Null/0 = no enforced minimum (checklist is
            // immediately available, matching the original behavior).
            $table->unsignedInteger('required_watch_seconds')->default(0)->after('youtube_channel_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('required_watch_seconds');
        });
    }
};

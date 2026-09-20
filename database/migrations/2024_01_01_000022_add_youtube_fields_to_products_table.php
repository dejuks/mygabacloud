<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Only meaningful when allow_free_unlock is true — the specific
            // video buyers must watch/engage with, and the channel to
            // subscribe to. Without these the free-unlock page has nothing
            // concrete to embed or link to.
            $table->string('youtube_video_url')->nullable()->after('allow_free_unlock');
            $table->string('youtube_channel_url')->nullable()->after('youtube_video_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['youtube_video_url', 'youtube_channel_url']);
        });
    }
};

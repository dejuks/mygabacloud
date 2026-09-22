<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row settings table (id = 1 always) for the "Download the App"
     * footer section. Kept separate from any generic `settings` table so it
     * can't collide with keys you may already use.
     */
    public function up(): void
    {
        Schema::create('app_download_settings', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_enabled')->default(false);
            $table->string('heading')->default('Get the Gaba Cloud App');
            $table->string('subheading')->nullable();

            // Android: either an APK uploaded here, or an external store link (not both required).
            $table->boolean('android_enabled')->default(false);
            $table->string('android_source')->default('apk'); // apk | play_store | amazon | custom_url
            $table->string('android_apk_path')->nullable();   // storage path when android_source = apk
            $table->string('android_apk_original_name')->nullable();
            $table->unsignedBigInteger('android_apk_size')->nullable();
            $table->string('android_version_name')->nullable();
            $table->string('android_play_store_url')->nullable();
            $table->string('android_amazon_url')->nullable();
            $table->string('android_custom_url')->nullable();

            // iOS is store-only (Apple does not allow direct APK-style installs).
            $table->boolean('ios_enabled')->default(false);
            $table->string('ios_app_store_url')->nullable();

            $table->unsignedBigInteger('android_download_count')->default(0);
            $table->timestamp('updated_at_by_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_download_settings');
    }
};

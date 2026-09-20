<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('engagement_unlocks', function (Blueprint $table) {
            // A checklist the buyer confirms, alongside the free-text
            // proof_note. Still fundamentally an honor-system + admin-judgment
            // flow (no API proves any of these happened), but a checklist is
            // more useful to the admin than parsing free text every time, and
            // makes the buyer explicitly confirm each step.
            $table->boolean('watched')->default(false)->after('user_id');
            $table->boolean('subscribed')->default(false)->after('watched');
            $table->boolean('liked')->default(false)->after('subscribed');
            $table->boolean('commented')->default(false)->after('liked');
            $table->boolean('shared')->default(false)->after('commented');
            $table->string('youtube_handle')->nullable()->after('shared'); // buyer's channel name/handle, for admin cross-check
        });
    }

    public function down(): void
    {
        Schema::table('engagement_unlocks', function (Blueprint $table) {
            $table->dropColumn(['watched', 'subscribed', 'liked', 'commented', 'shared', 'youtube_handle']);
        });
    }
};

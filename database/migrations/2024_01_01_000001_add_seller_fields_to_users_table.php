<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->text('bio')->nullable()->after('avatar');
            $table->boolean('is_seller')->default(false)->after('bio');
            $table->boolean('is_admin')->default(false)->after('is_seller');
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active')->after('is_admin');
            $table->string('paypal_email')->nullable()->after('status');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'bio', 'is_seller', 'is_admin', 'status', 'paypal_email']);
            $table->dropSoftDeletes();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Licenses issued through the free YouTube-engagement unlock path have
     * no purchase behind them — order_item_id must be nullable to support
     * that. This uses raw SQL (not Schema::table()->change(), which needs
     * doctrine/dbal) so it works without an extra Composer dependency.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE licenses ALTER COLUMN order_item_id DROP NOT NULL');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE licenses MODIFY order_item_id BIGINT UNSIGNED NULL');
            return;
        }

        // sqlite: local/dev only — if you hit this, `php artisan migrate:fresh` is simplest.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE licenses ALTER COLUMN order_item_id SET NOT NULL');
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE licenses MODIFY order_item_id BIGINT UNSIGNED NOT NULL');
        }
    }
};

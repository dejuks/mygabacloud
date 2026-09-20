<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same widening pattern used for orders.payment_method and
     * transactions.gateway earlier — Postgres implements enum() as a CHECK
     * constraint, so this drops and recreates it with 'revoked' added.
     */
    protected array $tables = [
        'manual_payment_proofs' => ['pending', 'approved', 'rejected', 'revoked'],
        'engagement_unlocks' => ['pending', 'approved', 'rejected', 'revoked'],
    ];

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        foreach ($this->tables as $table => $values) {
            $list = implode(',', array_map(fn ($v) => "'{$v}'", $values));

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_status_check");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_status_check CHECK (status IN ({$list}))");
                continue;
            }

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE {$table} MODIFY status ENUM({$list}) NOT NULL DEFAULT 'pending'");
            }

            // sqlite: local/dev only — `php artisan migrate:fresh` is simplest if you hit this.
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        foreach (array_keys($this->tables) as $table) {
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_status_check");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_status_check CHECK (status IN ('pending','approved','rejected'))");
                continue;
            }

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE {$table} MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
            }
        }
    }
};

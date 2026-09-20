<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $allowedMethods = ['stripe', 'paypal', 'cbe', 'telebirr', 'usdt_manual'];

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // Laravel's enum() on Postgres is implemented as a CHECK
            // constraint on a varchar column, not a native enum type — so
            // widening it is just replacing the constraint.
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            $list = implode(',', array_map(fn ($m) => "'{$m}'", $this->allowedMethods));
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ({$list}))");
            return;
        }

        if ($driver === 'mysql') {
            $list = implode(',', array_map(fn ($m) => "'{$m}'", $this->allowedMethods));
            DB::statement("ALTER TABLE orders MODIFY payment_method ENUM({$list}) NOT NULL");
            return;
        }

        // sqlite: CHECK constraints on an existing column can't be altered
        // without rebuilding the table. Local/dev use only — if you hit
        // this, the simplest fix is `php artisan migrate:fresh` on sqlite.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method IN ('stripe','paypal'))");
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('stripe','paypal') NOT NULL");
        }
    }
};

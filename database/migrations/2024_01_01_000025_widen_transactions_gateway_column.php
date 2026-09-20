<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same bug, same fix as 2024_01_01_000018: transactions.gateway was
     * still locked to the original ['stripe', 'paypal'] enum, so
     * OrderFulfilmentService::fulfil() — called from the manual-payment
     * approval path — fails its transactions insert with a Postgres CHECK
     * violation the moment a CBE/Telebirr/USDT-manual payment is approved.
     */
    protected array $allowedGateways = ['stripe', 'paypal', 'cbe', 'telebirr', 'usdt_manual'];

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_gateway_check');
            $list = implode(',', array_map(fn ($m) => "'{$m}'", $this->allowedGateways));
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_gateway_check CHECK (gateway IN ({$list}))");
            return;
        }

        if ($driver === 'mysql') {
            $list = implode(',', array_map(fn ($m) => "'{$m}'", $this->allowedGateways));
            DB::statement("ALTER TABLE transactions MODIFY gateway ENUM({$list}) NOT NULL");
            return;
        }

        // sqlite: local/dev only — `php artisan migrate:fresh` is simplest if you hit this.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_gateway_check');
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_gateway_check CHECK (gateway IN ('stripe','paypal'))");
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY gateway ENUM('stripe','paypal') NOT NULL");
        }
    }
};

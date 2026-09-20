<?php

namespace App\Console\Commands;

use App\Services\HeldEarningsReleaser;
use Illuminate\Console\Command;

class ReleaseHeldEarnings extends Command
{
    protected $signature = 'payouts:release-held';

    protected $description = 'Move seller earnings from pending to available once they clear the holding period';

    public function handle(HeldEarningsReleaser $releaser): int
    {
        $released = $releaser->release();

        $this->info("Released {$released} held earnings to available balance.");

        return self::SUCCESS;
    }
}

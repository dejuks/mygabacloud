<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\SellerWallet;

/**
 * Moves seller earnings from 'pending' to 'available' once they've cleared
 * the configured holding period. This exists as its own service (rather
 * than living only in the admin controller) so it can be called both from
 * the on-demand "Release cleared earnings" admin button AND from the
 * scheduled command that runs this automatically every day — the same
 * logic, not two copies that can drift apart.
 */
class HeldEarningsReleaser
{
    public function release(): int
    {
        $days = (int) PlatformSetting::get('payout_holding_days', 14);
        $released = 0;

        OrderItem::where('status', 'completed')
            ->where('created_at', '<=', now()->subDays($days))
            ->whereDoesntHave('order', fn ($q) => $q->where('status', 'refunded'))
            ->chunkById(200, function ($items) use (&$released) {
                foreach ($items as $item) {
                    $wallet = SellerWallet::where('seller_id', $item->seller_id)->first();
                    if ($wallet && $wallet->pending_balance >= $item->seller_earning) {
                        $wallet->releasePending((float) $item->seller_earning);
                        $released++;
                    }
                }
            });

        return $released;
    }
}

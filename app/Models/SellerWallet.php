<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SellerWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'available_balance', 'pending_balance', 'total_earned', 'total_withdrawn',
    ];

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class);
    }

    /**
     * Credit the wallet for a completed sale. Runs inside a transaction with
     * a row lock so concurrent sales/payouts never race on the balance.
     */
    public function creditSale(OrderItem $item): void
    {
        DB::transaction(function () use ($item) {
            $wallet = static::query()->lockForUpdate()->find($this->id);
            $wallet->increment('pending_balance', $item->seller_earning);
            $wallet->increment('total_earned', $item->seller_earning);

            $wallet->ledgerEntries()->create([
                'type' => 'sale_credit',
                'amount' => $item->seller_earning,
                'reference_type' => OrderItem::class,
                'reference_id' => $item->id,
                'note' => "Sale of order item #{$item->id}",
            ]);
        });
    }

    /** Move funds from pending to available once the refund/chargeback window passes. */
    public function releasePending(float $amount): void
    {
        DB::transaction(function () use ($amount) {
            $wallet = static::query()->lockForUpdate()->find($this->id);
            $wallet->decrement('pending_balance', $amount);
            $wallet->increment('available_balance', $amount);
        });
    }

    public function debitPayout(PayoutRequest $payout): void
    {
        DB::transaction(function () use ($payout) {
            $wallet = static::query()->lockForUpdate()->find($this->id);
            $wallet->decrement('available_balance', $payout->amount);
            $wallet->increment('total_withdrawn', $payout->amount);

            $wallet->ledgerEntries()->create([
                'type' => 'payout_debit',
                'amount' => -$payout->amount,
                'reference_type' => PayoutRequest::class,
                'reference_id' => $payout->id,
                'note' => "Payout #{$payout->id}",
            ]);
        });
    }
}

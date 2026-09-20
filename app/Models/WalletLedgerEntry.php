<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = ['seller_wallet_id', 'type', 'amount', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(SellerWallet::class, 'seller_wallet_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

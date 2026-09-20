<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'seller_id', 'license_type', 'price',
        'commission_rate', 'commission_amount', 'seller_earning', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'seller_earning' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function license(): HasOne
    {
        return $this->hasOne(License::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Compute and store the commission split. Call this ONCE at purchase time —
     * the snapshot on this row is the permanent source of truth for payouts,
     * even if the seller's commission rate changes later.
     */
    public static function calculateSplit(float $price, float $commissionRatePercent): array
    {
        $commissionAmount = round($price * ($commissionRatePercent / 100), 2);
        $sellerEarning = round($price - $commissionAmount, 2);

        return compact('commissionAmount', 'sellerEarning');
    }
}

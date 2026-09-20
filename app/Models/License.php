<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id', 'buyer_id', 'product_id', 'license_key',
        'type', 'status', 'activations_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (License $license) {
            $license->license_key ??= static::generateKey();
        });
    }

    public static function generateKey(): string
    {
        return implode('-', str_split(strtoupper(Str::random(16)), 4));
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

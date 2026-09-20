<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'store_name', 'slug', 'description', 'website',
        'application_status', 'is_verified', 'custom_commission_rate',
        'total_sales', 'average_rating', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'custom_commission_rate' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Effective commission rate for this seller — falls back to the
     * platform-wide default set in platform_settings.
     */
    public function commissionRate(): float
    {
        return (float) ($this->custom_commission_rate
            ?? PlatformSetting::get('default_commission_rate', 30));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'type', 'value', 'usage_limit', 'used_count', 'expires_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;

        return true;
    }

    public function discountFor(float $amount): float
    {
        return $this->type === 'percentage'
            ? round($amount * ($this->value / 100), 2)
            : min($this->value, $amount);
    }
}

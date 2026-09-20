<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualPaymentProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'method', 'proof_path', 'reference_note',
        'status', 'reviewed_by', 'reviewed_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function methodLabel(string $method): string
    {
        return match ($method) {
            'cbe' => 'CBE Bank Transfer',
            'telebirr' => 'Telebirr',
            'usdt_manual' => 'USDT (TRC20)',
            default => ucfirst($method),
        };
    }
}

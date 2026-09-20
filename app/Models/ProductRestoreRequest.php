<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRestoreRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'seller_id', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        // withTrashed() because the whole point of this relation is
        // pointing at a soft-deleted product — the default relation query
        // would silently return null otherwise.
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

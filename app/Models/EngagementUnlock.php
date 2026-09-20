<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngagementUnlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'license_id', 'user_id', 'proof_url', 'proof_screenshot_path', 'proof_screenshot_original_name',
        'proof_note', 'youtube_handle', 'watched_seconds',
        'watched', 'subscribed', 'liked', 'commented', 'shared',
        'status', 'reviewed_by', 'reviewed_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'watched' => 'boolean',
            'subscribed' => 'boolean',
            'liked' => 'boolean',
            'commented' => 'boolean',
            'shared' => 'boolean',
        ];
    }

    public function checklistComplete(): bool
    {
        return $this->watched && $this->subscribed && $this->liked && $this->commented && $this->shared;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

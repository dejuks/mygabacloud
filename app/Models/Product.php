<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'seller_id', 'category_id', 'title', 'slug', 'short_description', 'description',
        'thumbnail', 'regular_price', 'extended_price', 'demo_url', 'framework',
        'current_version', 'compatible_with', 'status', 'allow_free_unlock',
        'youtube_video_url', 'youtube_channel_url', 'required_watch_seconds', 'rejection_reason',
        'is_featured', 'sale_price', 'sale_ends_at',
        'reviewed_by', 'reviewed_at', 'published_at',
        // Denormalized counters. In normal application flow these are only ever
        // touched via increment()/decrement() (which bypass $fillable anyway) —
        // they're listed here so seeders can set realistic starting values.
        // Seller/admin controllers never mass-assign these from request input.
        'sales_count', 'views_count', 'average_rating', 'reviews_count',
    ];

    protected function casts(): array
    {
        return [
            'regular_price' => 'decimal:2',
            'extended_price' => 'decimal:2',
            'compatible_with' => 'array',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'average_rating' => 'decimal:2',
            'allow_free_unlock' => 'boolean',
            'is_featured' => 'boolean',
            'sale_price' => 'decimal:2',
            'sale_ends_at' => 'datetime',
        ];
    }

    // --- Relationships ---

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductFile::class);
    }

    public function latestFile(): HasMany
    {
        return $this->files()->latest();
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function changelogs(): HasMany
    {
        return $this->hasMany(ProductChangelog::class)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function engagementUnlocks(): HasMany
    {
        return $this->hasMany(EngagementUnlock::class);
    }

    // --- Query Scopes ---

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'approved')->whereNotNull('published_at');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    // --- Helpers ---

    public function priceFor(string $licenseType): float
    {
        if ($licenseType === 'extended') {
            return (float) $this->extended_price;
        }

        return $this->isOnSale() ? (float) $this->sale_price : (float) $this->regular_price;
    }

    /**
     * A sale is active when a sale_price is set, it's genuinely lower than
     * the regular price (never let a sale accidentally raise the price),
     * and either there's no expiry or it hasn't passed yet.
     */
    public function isOnSale(): bool
    {
        $salePrice = (float) ($this->sale_price ?? 0);

        if ($salePrice <= 0 || $salePrice >= (float) $this->regular_price) {
            return false;
        }

        return ! $this->sale_ends_at || $this->sale_ends_at->isFuture();
    }

    public function discountPercent(): int
    {
        if (! $this->isOnSale()) {
            return 0;
        }

        return (int) round((1 - ((float) $this->sale_price / (float) $this->regular_price)) * 100);
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    /**
     * Extracts the video ID from any common YouTube URL shape
     * (watch?v=, youtu.be/, shorts/, embed/) so it can be embedded.
     * Returns null if youtube_video_url isn't set or doesn't parse.
     */
    public function youtubeVideoId(): ?string
    {
        if (! $this->youtube_video_url) {
            return null;
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?v=)([\w-]{11})/',
            '/(?:youtu\.be\/)([\w-]{11})/',
            '/(?:youtube\.com\/shorts\/)([\w-]{11})/',
            '/(?:youtube\.com\/embed\/)([\w-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->youtube_video_url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Maps the `products` table (verified against the SQL dump) to JSON.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reviews = (int) data_get($this, 'reviews_count', 0);

        return [
            'id'            => $this->id,
            'title'         => data_get($this, 'title'),
            'slug'          => $this->slug,
            'thumbnail'     => $this->thumbnailUrl(),
            'price'         => $this->money(data_get($this, 'regular_price')),
            'sale_price'    => $this->activeSalePrice(),
            'category'      => $this->relationLoaded('category') && $this->category
                                ? new CategoryResource($this->category)
                                : null,
            'rating_avg'    => $reviews > 0 ? round((float) data_get($this, 'average_rating', 0), 1) : null,
            'reviews_count' => $reviews,
            'sales_count'   => (int) data_get($this, 'sales_count', 0),
        ];
    }

    /** sale_price only counts while sale_ends_at is empty or still in the future. */
    protected function activeSalePrice(): ?float
    {
        $sale    = data_get($this, 'sale_price');
        $regular = data_get($this, 'regular_price');
        if ($sale === null || ($regular !== null && (float) $sale >= (float) $regular)) {
            return null;
        }

        $ends = data_get($this, 'sale_ends_at');
        if ($ends !== null && Carbon::parse($ends)->isPast()) {
            return null;
        }

        return $this->money($sale);
    }

    protected function thumbnailUrl(): ?string
    {
        $path = data_get($this, 'thumbnail');
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/' . ltrim($path, '/'));
    }

    protected function money($value): ?float
    {
        return $value === null ? null : round((float) $value, 2);
    }
}

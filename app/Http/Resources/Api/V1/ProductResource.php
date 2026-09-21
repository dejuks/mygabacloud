<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Field mapping between the marketplace's Product model and the JSON the
 * Android app receives. If a column is named differently in your schema,
 * this is the only file that needs to change (see README "Assumptions").
 * data_get() returns null for missing attributes instead of throwing.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => data_get($this, 'title') ?? data_get($this, 'name'),
            'slug'         => $this->slug,
            'thumbnail'    => $this->thumbnailUrl(),
            'price'        => $this->money(data_get($this, 'price')),
            'sale_price'   => $this->money(data_get($this, 'sale_price')),
            'category'     => new CategoryResource($this->whenLoaded('category')),
            'rating_avg'   => $this->when(isset($this->reviews_avg_rating), fn () => round((float) $this->reviews_avg_rating, 1)),
            'reviews_count' => $this->whenCounted('reviews'),
            'sales_count'  => data_get($this, 'sales_count'),
        ];
    }

    protected function thumbnailUrl(): ?string
    {
        $path = data_get($this, 'thumbnail') ?? data_get($this, 'thumbnail_path');

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

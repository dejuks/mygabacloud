<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'description'    => data_get($this, 'description'),
            'demo_url'       => data_get($this, 'demo_url'),
            'extended_price' => $this->money(data_get($this, 'extended_price')),
            'seller'         => $this->when($this->relationLoaded('seller') && $this->seller, fn () => [
                'id'   => $this->seller->id,
                'name' => data_get($this->seller, 'store_name') ?? $this->seller->name,
            ]),
            'updated_at'     => optional(data_get($this, 'updated_at'))->toIso8601String(),
        ]);
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'short_description' => data_get($this, 'short_description'),
            'description'       => data_get($this, 'description'),
            'demo_url'          => data_get($this, 'demo_url'),
            'extended_price'    => $this->money(data_get($this, 'extended_price')),
            'version'           => data_get($this, 'current_version'),
            'framework'         => data_get($this, 'framework'),
        ]);
    }
}

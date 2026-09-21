<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'license_key' => data_get($this, 'license_key') ?? data_get($this, 'key'),
            'type'        => data_get($this, 'type') ?? data_get($this, 'license_type'),
            'product'     => new ProductResource($this->whenLoaded('product')),
            'acquired_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

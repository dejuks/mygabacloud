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
            'license_key' => $this->license_key,
            'type'        => $this->type,
            'status'      => $this->status,
            'product'     => $this->relationLoaded('product') && $this->product
                                ? new ProductResource($this->product)
                                : null,
            'acquired_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

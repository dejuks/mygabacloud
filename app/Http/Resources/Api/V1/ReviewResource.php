<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'rating'     => (int) $this->rating,
            'comment'    => data_get($this, 'comment') ?? data_get($this, 'body'),
            'author'     => optional($this->user)->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources\V1\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,

            'usage_limit' => $this->usage_limit,
            'user_usage_limit' => $this->user_usage_limit,
            'used' => $this->used,

            'is_active' => $this->is_active,
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}

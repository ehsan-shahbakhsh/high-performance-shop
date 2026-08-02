<?php

namespace App\Http\Resources\V1\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
            'name' => $this->name,
            'driver' => $this->driver,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'position' => $this->position,
        ];
    }
}

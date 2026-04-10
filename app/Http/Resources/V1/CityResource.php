<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
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
            'slug' => $this->slug,

            'has_shipping' => $this->has_shipping,

            'coordinates' => [
                'latitude' => (float)$this->latitude,
                'longitude' => (float)$this->longitude,
            ],
        ];
    }
}

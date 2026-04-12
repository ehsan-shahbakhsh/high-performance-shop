<?php

namespace App\Http\Resources\V1\Customer;

use App\Http\Resources\V1\CityResource;
use App\Http\Resources\V1\ProvinceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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

            'recipient' => [
                'first_name' => $this->recipient_first_name,
                'last_name' => $this->recipient_last_name,
                'mobile' => $this->recipient_mobile,
            ],

            'province' => ProvinceResource::make($this->whenLoaded('province')),
            'city' => CityResource::make($this->whenLoaded('city')),

            'title' => $this->title,
            'address_line' => $this->address_line,
            'plaque' => $this->plaque,
            'unit' => $this->unit,
            'postal_code' => $this->postal_code,

            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],

            'is_default' => $this->is_default,
        ];
    }
}

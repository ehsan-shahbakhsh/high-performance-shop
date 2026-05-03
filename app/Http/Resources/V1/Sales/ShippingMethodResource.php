<?php

namespace App\Http\Resources\V1\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodResource extends JsonResource
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
            'description' => $this->description,

            'is_cod_supported' => $this->is_cod_supported,

            'cost' => $this->calculated_cost,
            'formatted_cost' => number_format($this->calculated_cost) . ' تومان',

            'cod_cost' => $this->calculated_cod_cost,
            'formatted_cod_cost' => $this->calculated_cod_cost != null ? number_format($this->calculated_cod_cost) . ' تومان' : null,

            'cod_surcharge' => $this->cod_surcharge,
            'formatted_cod_surcharge' => number_format($this->cod_surcharge) . ' تومان',
        ];
    }
}

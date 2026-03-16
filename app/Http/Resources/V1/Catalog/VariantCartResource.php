<?php

namespace App\Http\Resources\V1\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariantCartResource extends JsonResource
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
            'sku' => $this->sku,

            'attributes' => $this->attributes,

            'availability' => [
                'is_active' => $this->is_active,
                'stock_quantity' => $this->stock_quantity,
            ],
        ];
    }
}

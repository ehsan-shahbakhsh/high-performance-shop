<?php

namespace App\Http\Resources\V1\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
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

            'availability' => [
                'is_active' => $this->is_active,
                'stock_quantity' => $this->stock_quantity,
            ],

            'pricing' => [
                'price' => $this->price,
                'sale_price' => $this->sale_price,
                'sale_start' => $this->sale_start,
                'sale_end' => $this->sale_end,
            ],

            'dimensions' => [
                'weight' => $this->weight,
                'length' => $this->length,
                'width' => $this->width,
                'height' => $this->height,
            ],

            'position' => $this->position,
            'is_default' => $this->is_default,

            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}

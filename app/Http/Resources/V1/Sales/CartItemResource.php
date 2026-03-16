<?php

namespace App\Http\Resources\V1\Sales;

use App\Http\Resources\V1\Catalog\ProductCartResource;
use App\Http\Resources\V1\Catalog\VariantCartResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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
            'quantity' => $this->quantity,
            'unit_price_snapshot' => $this->unit_price_snapshot,

            'references' => [
                'product_id' => $this->product_id,
                'variant_id' => $this->variant_id,
            ],

            'product' => ProductCartResource::make($this->whenLoaded('product')),
            'variant' => VariantCartResource::make($this->whenLoaded('variant')),

            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}

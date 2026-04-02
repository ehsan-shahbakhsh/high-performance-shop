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
        $isPersisted = $this->resource->exists;

        return [
            'id' => $isPersisted ? $this->id : $this->product_variant_id,

            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'price_when_added' => $this->price_when_added,

            'product' => ProductCartResource::make($this->whenLoaded('variant.product')),
            'variant' => VariantCartResource::make($this->whenLoaded('variant')),

            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}

<?php

namespace App\Http\Resources\V1\Sales;

use App\Http\Resources\V1\Catalog\ProductWishlistResource;
use App\Http\Resources\V1\Catalog\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
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

            'product' => $this->when(
                $this->relationLoaded('variant') && $this->variant->relationLoaded('product'),
                fn() => ProductWishlistResource::make($this->variant->product),
            ),
            'variant' => VariantResource::make($this->whenLoaded('variant')),
        ];
    }
}

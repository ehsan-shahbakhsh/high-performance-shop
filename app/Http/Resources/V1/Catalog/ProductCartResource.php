<?php

namespace App\Http\Resources\V1\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCartResource extends JsonResource
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
            'sku' => $this->sku,

            'price' => $this->price,
            'sale_price' => $this->sale_price,

            'is_active' => $this->is_active,

            'thumbnail_url' => $this->thumbnail_url,

            'out_of_stock_action' => $this->out_of_stock_action,
            'custom_stock_text' => $this->custom_stock_text,

            'short_description' => $this->short_description,
        ];
    }
}

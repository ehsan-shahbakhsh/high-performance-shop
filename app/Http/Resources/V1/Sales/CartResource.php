<?php

namespace App\Http\Resources\V1\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,

            'financials' => [
                'subtotal' => $this->subtotal,
                'discount_total' => $this->discount_total,
                'shipping_total' => $this->shipping_total,
                'total' => $this->total,
            ],

            'summary' => [
                'items_count' => $this->items_count,
                'total_quantity' => $this->items_qty_sum,
            ],

            'is_locked' => !is_null($this->locked_at),

            'timestamps' => [
                'last_activity_at' => $this->last_activity_at,
                'created_at' => $this->created_at,
                'completed_at' => $this->whenNotNull($this->completed_at),
            ],

            'meta' => $this->meta,

            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

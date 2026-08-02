<?php

namespace App\Http\Resources\V1\Sales;

use App\Http\Resources\V1\Customer\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartSummaryResource extends JsonResource
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
            'version' => $this->version,

            'shipping_method' => ShippingMethodResource::make($this->whenLoaded('shippingMethod')),
            'shipping_address' => AddressResource::make($this->whenLoaded('shippingAddress')),
            'payment_method' => PaymentMethodResource::make($this->whenLoaded('paymentMethod')),
            'coupon' => CouponResource::make($this->whenLoaded('coupon')),
            'items' => CartItemResource::collection($this->whenLoaded('items')),

            'financials' => [
                'subtotal' => $this->subtotal,
                'discount_total' => $this->discount_total,
                'shipping_total' => $this->shipping_total,
                'total' => $this->total,
            ],
        ];
    }
}

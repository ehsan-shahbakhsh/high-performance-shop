<?php

namespace App\Models;

use Database\Factories\ShippingRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    /** @use HasFactory<ShippingRateFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shipping_zone_id',
        'shipping_method_id',
        'is_active',
        'position',
        'base_price',
        'price_per_kg',
        'free_shipping_over',
        'cod_fee',
        'min_weight',
        'max_weight',
        'min_subtotal',
        'max_subtotal',
        'min_delivery_time',
        'max_delivery_time',
        'conditions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'base_price' => 'integer',
        'price_per_kg' => 'integer',
        'free_shipping_over' => 'integer',
        'cod_fee' => 'integer',
        'min_weight' => 'integer',
        'max_weight' => 'integer',
        'min_subtotal' => 'integer',
        'max_subtotal' => 'integer',
        'min_delivery_time' => 'integer',
        'max_delivery_time' => 'integer',
        'conditions' => 'json',
    ];

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}

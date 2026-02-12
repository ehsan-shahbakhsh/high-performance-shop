<?php

namespace App\Models;

use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'carrier_id',
        'code',
        'name',
        'description',
        'min_delivery_time',
        'max_delivery_time',
        'is_cod_supported',
        'max_weight',
        'settings',
        'is_active',
        'position',
    ];

    protected $casts = [
        'settings' => 'json',
        'is_cod_supported' => 'boolean',
        'is_active' => 'boolean',
        'min_delivery_time' => 'integer',
        'max_delivery_time' => 'integer',
        'position' => 'integer',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }
}

<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'sku',
        'product_name',
        'variant_name',
        'selected_options',
        'snapshot',
        'quantity',
        'original_unit_price',
        'sale_unit_price',
        'promotion_discount',
        'final_unit_price',
        'line_total',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->belongsTo(ProductVariant::class)->withTrashed();
    }
}

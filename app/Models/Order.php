<?php

namespace App\Models;

use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\OrderPaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_status',
        'items_subtotal',
        'items_sale_discount',
        'cart_discount',
        'discount_total',
        'shipping_total',
        'subtotal',
        'grand_total',
        'discount_breakdown',
        'shipping_address_id',
        'shipping_address_snapshot',
        'shipping_method_id',
        'shipping_method_snapshot',
        'payment_method_id',
        'payment_driver',
        'payment_method_snapshot',
        'tracking_number',
        'customer_notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => OrderPaymentStatus::class,
        'items_subtotal' => 'integer',
        'items_sale_discount' => 'integer',
        'cart_discount' => 'integer',
        'discount_total' => 'integer',
        'shipping_total' => 'integer',
        'subtotal' => 'integer',
        'grand_total' => 'integer',
        'discount_breakdown' => 'json:unicode',
        'shipping_address_snapshot' => 'json:unicode',
        'shipping_method_snapshot' => 'json:unicode',
        'payment_method_snapshot' => 'json:unicode',
    ];

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)
            ->orderBy('created_at');
    }

    public function latestStatusHistory(): HasOne
    {
        return $this->hasOne(OrderStatusHistory::class)
            ->latestOfMany();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }
}

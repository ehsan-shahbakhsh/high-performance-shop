<?php

namespace App\Models;

use App\Enums\Sales\OrderStatus;
use Database\Factories\OrderStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    /** @use HasFactory<OrderStatusHistoryFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'old_status', 'new_status', 'user_id', 'comment', 'customer_notified', 'metadata'];

    protected $casts = [
        'old_status' => OrderStatus::class,
        'new_status' => OrderStatus::class,
        'customer_notified' => 'boolean',
        'metadata' => 'json',
    ];

    const UPDATED_AT = null;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\DiscountUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountUsage extends Model
{
    /** @use HasFactory<DiscountUsageFactory> */
    use HasFactory;

    protected $fillable = ['discount_id', 'coupon_id', 'user_id', 'order_id', 'amount'];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

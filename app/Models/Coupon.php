<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['discount_id', 'code', 'usage_limit', 'user_usage_limit', 'used', 'is_active', 'expires_at'];

    protected $casts = [
        'usage_limit' => 'integer',
        'user_usage_limit' => 'integer',
        'used' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}

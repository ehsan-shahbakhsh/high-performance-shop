<?php

namespace App\Models;

use Database\Factories\DiscountableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Discountable extends Model
{
    /** @use HasFactory<DiscountableFactory> */
    use HasFactory;

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'discount_id',
        'discountable_id',
        'discountable_type',
        'is_excluded',
    ];

    protected $casts = [
        'is_excluded' => 'boolean',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function discountable(): MorphTo
    {
        return $this->morphTo();
    }
}

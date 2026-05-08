<?php

namespace App\Models;

use App\Data\Promotions\DiscountActionSettingsData;
use App\Enums\DiscountConditionMatchType;
use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_automatic',
        'type',
        'amount',
        'max_discount_amount',
        'starts_at',
        'ends_at',
        'action_settings',
        'condition_match_type',
        'usage_limit',
        'user_usage_limit',
        'used',
        'is_exclusive',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_automatic' => 'boolean',
        'type' => DiscountType::class,
        'scope' => DiscountScope::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_discount_amount' => 'integer',
        'action_settings' => DiscountActionSettingsData::class,
        'condition_match_type' => DiscountConditionMatchType::class,
        'usage_limit' => 'integer',
        'user_usage_limit' => 'integer',
        'used' => 'integer',
        'is_exclusive' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(DiscountRule::class);
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'discountable')
            ->withPivot('is_excluded');
    }

    public function categories(): MorphToMany
    {
        return $this->morphedByMany(ProductCategory::class, 'discountable')
            ->withPivot('is_excluded');
    }

    public function brands(): MorphToMany
    {
        return $this->morphedByMany(Brand::class, 'discountable')
            ->withPivot('is_excluded');
    }

    public function discountables(): HasMany
    {
        return $this->hasMany(Discountable::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }
}

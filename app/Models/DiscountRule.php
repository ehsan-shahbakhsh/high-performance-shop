<?php

namespace App\Models;

use App\Enums\DiscountRuleOperator;
use App\Enums\DiscountRuleType;
use Database\Factories\DiscountRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRule extends Model
{
    /** @use HasFactory<DiscountRuleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['discount_id', 'type', 'operator', 'value'];

    protected $casts = [
        'type' => DiscountRuleType::class,
        'operator' => DiscountRuleOperator::class,
        'value' => 'json',
    ];
}

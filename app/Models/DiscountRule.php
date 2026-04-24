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
    protected $fillable = [
        'discount_id',
        'type',
        'operator',
        'value_string',
        'value_integer',
        'value_float',
        'value_boolean',
        'value_json',
    ];

    protected $casts = [
        'type' => DiscountRuleType::class,
        'operator' => DiscountRuleOperator::class,
        'value_integer' => 'integer',
        'value_float' => 'float',
        'value_boolean' => 'boolean',
        'value_json' => 'json',
    ];
}

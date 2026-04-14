<?php

namespace App\Data\Shipping;

use App\Enums\Shipping\ConditionType;
use App\Enums\Shipping\Operator;
use Spatie\LaravelData\Data;

class ShippingRuleData extends Data
{
    public function __construct(
        public ConditionType $type,
        public Operator      $operator,
        public ?int          $valueId = null,
        public ?array        $valueIds = null,
        public mixed         $value = null,
    )
    {
    }
}

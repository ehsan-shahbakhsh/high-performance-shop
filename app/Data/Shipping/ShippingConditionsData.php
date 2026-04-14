<?php

namespace App\Data\Shipping;

use App\Enums\Shipping\MatchType;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ShippingConditionsData extends Data
{
    public function __construct(
        public MatchType      $matchType,

        #[DataCollectionOf(ShippingRuleData::class)]
        public DataCollection $rules,
    )
    {
    }
}

<?php

namespace App\Data\Promotions;

use App\Enums\DiscountStrategy;
use Spatie\LaravelData\Attributes\Validation\{Max, Min, RequiredIf};
use Spatie\LaravelData\Data;

class BogoActionSettingsData extends Data
{
    public function __construct(
        #[Min(1)]
        public int              $buyQty,

        #[Min(1)]
        public int              $getQty,

        #[Min(1), Max(100)]
        public int              $discountPercent,

        public DiscountStrategy $strategy,

        #[RequiredIf('strategy', DiscountStrategy::Specific)]
        public ?int             $targetVariantId = null,

        #[Min(1)]
        public ?int             $maxApplicationsPerOrder = null,
    )
    {
    }
}

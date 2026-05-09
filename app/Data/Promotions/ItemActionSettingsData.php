<?php

namespace App\Data\Promotions;

use App\Enums\DiscountStrategy;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Data;

class ItemActionSettingsData extends Data
{
    public function __construct(
        public ?DiscountStrategy $strategy = null,

        #[RequiredIf('strategy', DiscountStrategy::Specific)]
        public ?int              $targetVariantId = null,

        #[Min(1)]
        public ?int              $maxApplicationsPerOrder = null,
    )
    {
    }
}

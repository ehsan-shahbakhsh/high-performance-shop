<?php

namespace App\Data\Promotions;

use Spatie\LaravelData\Data;

class DiscountActionSettingsData extends Data
{
    public function __construct(
        public ?BogoActionSettingsData $bogo = null,
        public ?ItemActionSettingsData $item = null,
    ) {}
}

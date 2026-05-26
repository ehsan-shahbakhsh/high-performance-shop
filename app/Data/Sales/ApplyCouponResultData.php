<?php

namespace App\Data\Sales;

use Spatie\LaravelData\Data;

class ApplyCouponResultData extends Data
{
    public function __construct(
        public string $message,
        public CartDiscountResultData $cartSummary,
    ) {}
}

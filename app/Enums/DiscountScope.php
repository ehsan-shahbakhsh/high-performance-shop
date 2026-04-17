<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DiscountScope: string implements HasLabel, HasColor
{
    case Order = 'order';
    case Item = 'item';
    case Shipping = 'shipping';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Order => 'کل سبد خرید (Order)',
            self::Item => 'محصولات انتخابی (Item)',
            self::Shipping => 'هزینه ارسال (Shipping)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Order => 'success',
            self::Item => 'info',
            self::Shipping => 'warning',
        };
    }
}

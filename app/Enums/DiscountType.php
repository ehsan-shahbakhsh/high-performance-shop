<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DiscountType: string implements HasLabel, HasColor
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case FreeShipping = 'free_shipping';
    case BuyXGetY = 'buy_x_get_y';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Fixed => 'مبلغ ثابت',
            self::Percentage => 'درصدی',
            self::FreeShipping => 'ارسال رایگان',
            self::BuyXGetY => 'یکی بخر یکی ببر (BOGO)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Fixed => 'success',
            self::Percentage => 'info',
            self::FreeShipping => 'warning',
            self::BuyXGetY => 'gray',
        };
    }
}

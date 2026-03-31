<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProductBundleItemModifierType: string implements HasLabel, HasColor, HasIcon
{
    case None = 'none';
    case FixedDiscount = 'fixed_discount';
    case FixedPrice = 'fixed_price';
    case Percentage = 'percentage';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::None => 'بدون تغییر قیمت',
            self::Percentage => 'درصد تخفیف',
            self::FixedDiscount => 'مبلغ تخفیف ثابت',
            self::FixedPrice => 'قیمت ثابت جایگزین',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::None => 'gray',
            self::Percentage => 'success',
            self::FixedDiscount => 'info',
            self::FixedPrice => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::None => 'heroicon-m-minus',
            self::Percentage => 'heroicon-m-receipt-percent',
            self::FixedDiscount => 'heroicon-m-minus-circle',
            self::FixedPrice => 'heroicon-m-tag',
        };
    }
}

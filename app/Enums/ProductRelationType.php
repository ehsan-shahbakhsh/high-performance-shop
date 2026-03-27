<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProductRelationType: string implements HasLabel, HasColor, HasIcon
{
    case Related = 'related';
    case Upsell = 'upsell';
    case CrossSell = 'cross_sell';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Related => 'محصولات مرتبط',
            self::Upsell => 'پیشنهاد ارتقا (Upsell)',
            self::CrossSell => 'کالای مکمل (Cross-sell)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Related => 'info',
            self::Upsell => 'success',
            self::CrossSell => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Related => 'heroicon-o-link',
            self::Upsell => 'heroicon-o-arrow-trending-up',
            self::CrossSell => 'heroicon-o-puzzle-piece',
        };
    }
}
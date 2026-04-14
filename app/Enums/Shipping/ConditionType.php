<?php

namespace App\Enums\Shipping;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ConditionType: string implements HasLabel
{
    case Categories = 'categories';
    case Products = 'products';
    case Brands = 'brands';
    case ItemsCount = 'items_count';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Categories => 'دسته‌بندی‌ها',
            self::Products => 'محصولات',
            self::Brands => 'برندها',
            self::ItemsCount => 'تعداد اقلام سبد خرید',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DiscountConditionMatchType: string implements HasLabel
{
    case All = 'all';
    case Any = 'any';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::All => 'همه شروط برقرار باشد',
            self::Any => 'حداقل یک شرط برقرار باشد',
        };
    }
}

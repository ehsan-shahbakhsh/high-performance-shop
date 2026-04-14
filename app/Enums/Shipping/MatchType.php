<?php

namespace App\Enums\Shipping;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum MatchType: string implements HasLabel
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

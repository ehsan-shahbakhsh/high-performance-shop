<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum ShippingZoneLocationType: string implements HasLabel, HasColor, HasIcon
{
    case Include = 'include';
    case Exclude = 'exclude';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Include => 'شامل شود',
            self::Exclude => 'مستثنی شود (حذف)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Include => 'success',
            self::Exclude => 'danger',
        };
    }

    public function getIcon(): string|\BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Include => Heroicon::OutlinedCheckCircle,
            self::Exclude => Heroicon::OutlinedMinusCircle,
        };
    }
}

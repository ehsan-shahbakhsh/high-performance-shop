<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TagType: string implements HasLabel, HasColor, HasIcon
{
    case General = 'general';
    case Feature = 'feature';
    case Event = 'event';
    case Badge = 'badge';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::General => 'عمومی',
            self::Feature => 'ویژگی فنی',
            self::Event => 'مناسبتی',
            self::Badge => 'نشان (Badge)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::General => 'gray',
            self::Feature => 'info',
            self::Event => 'warning',
            self::Badge => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::General => 'heroicon-o-tag',
            self::Feature => 'heroicon-o-cpu-chip',
            self::Event => 'heroicon-o-cake',
            self::Badge => 'heroicon-o-check-badge',
        };
    }
}
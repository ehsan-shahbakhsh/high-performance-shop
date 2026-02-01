<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProductType: string implements HasLabel, HasColor, HasIcon
{
    case Simple = 'simple';
    case Variable = 'variable';
    case Virtual = 'virtual';
    case Downloadable = 'downloadable';
    case Bundle = 'bundle';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Simple => 'محصول ساده',
            self::Variable => 'محصول متغیر',
            self::Virtual => 'محصول مجازی',
            self::Downloadable => 'محصول دانلودی',
            self::Bundle => 'باندل (پکیج)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Simple => 'info',
            self::Variable => 'warning',
            self::Virtual => 'success',
            self::Downloadable => 'teal',
            self::Bundle => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Simple => 'heroicon-o-cube',
            self::Variable => 'heroicon-o-swatch',
            self::Virtual => 'heroicon-o-cloud',
            self::Downloadable => 'heroicon-o-arrow-down-tray',
            self::Bundle => 'heroicon-o-rectangle-stack',
        };
    }
}

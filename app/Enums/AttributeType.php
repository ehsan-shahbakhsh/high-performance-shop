<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;

enum AttributeType: string implements HasLabel, HasIcon, HasColor
{
    // Basic Types
    case Text = 'text';
    case Textarea = 'textarea';
    case Boolean = 'boolean';

    // Numeric (Critical for Filtering)
    case Number = 'number';

    // Selection & Visuals
    case Color = 'color';
    case Select = 'select';
    case MultiSelect = 'multi_select';

    // Advanced
    case Date = 'date';
    case File = 'file';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Text => 'متن کوتاه',
            self::Textarea => 'متن طولانی',
            self::Boolean => 'بله/خیر (سوییچ)',
            self::Number => 'عدد (وزن، ابعاد...)',
            self::Color => 'رنگ',
            self::Select => 'تک انتخابی (دراپ‌داون)',
            self::MultiSelect => 'چند انتخابی',
            self::Date => 'تاریخ',
            self::File => 'فایل دانلودی',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Text => 'heroicon-o-bars-2',
            self::Textarea => 'heroicon-o-bars-3-bottom-left',
            self::Boolean => 'heroicon-o-check-circle',
            self::Number => 'heroicon-o-calculator',
            self::Color => 'heroicon-o-swatch',
            self::Select => 'heroicon-o-list-bullet',
            self::MultiSelect => 'heroicon-o-rectangle-stack',
            self::Date => 'heroicon-o-calendar',
            self::File => 'heroicon-o-paper-clip',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Text, self::Textarea => 'gray',
            self::Boolean => 'success',
            self::Number => 'primary',
            self::Color => 'warning',
            self::Select, self::MultiSelect => 'info',
            self::Date => 'danger',
            self::File => 'secondary',
        };
    }

    public function hasOptions(): bool
    {
        return match ($this) {
            self::Select, self::MultiSelect => true,
            default => false,
        };
    }
}

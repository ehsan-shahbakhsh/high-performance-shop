<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;

enum AttributeType: string implements HasLabel, HasIcon, HasColor
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Boolean = 'boolean';
    case Number = 'number';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Date = 'date';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Text => 'متن کوتاه',
            self::Textarea => 'متن طولانی',
            self::Boolean => 'بله/خیر (سوییچ)',
            self::Number => 'عدد (وزن، ابعاد...)',
            self::Select => 'تک انتخابی (دراپ‌داون)',
            self::MultiSelect => 'چند انتخابی',
            self::Date => 'تاریخ',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Text => 'heroicon-o-bars-2',
            self::Textarea => 'heroicon-o-bars-3-bottom-left',
            self::Boolean => 'heroicon-o-check-circle',
            self::Number => 'heroicon-o-calculator',
            self::Select => 'heroicon-o-list-bullet',
            self::MultiSelect => 'heroicon-o-rectangle-stack',
            self::Date => 'heroicon-o-calendar',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Text, self::Textarea => 'gray',
            self::Boolean => 'success',
            self::Number => 'primary',
            self::Select, self::MultiSelect => 'info',
            self::Date => 'danger',
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

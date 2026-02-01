<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ProductOutOfStockAction: string implements HasLabel, HasIcon, HasColor
{
    case Default = 'default';
    case Hidden = 'hidden';
    case Text = 'text';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Default => 'نمایش قیمت + برچسب (پیش‌فرض)',
            self::Hidden => 'مخفی کردن کامل (عدم نمایش)',
            self::Text => 'نمایش متن جایگزین (مثل: تماس بگیرید)',
        };
    }

    public function getIcon(): string|Htmlable|null
    {
        return match ($this) {
            self::Default => 'heroicon-m-eye',
            self::Hidden => 'heroicon-m-eye-slash',
            self::Text => 'heroicon-m-chat-bubble-bottom-center-text',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Default => 'gray',
            self::Hidden => 'danger',
            self::Text => 'info',
        };
    }
}

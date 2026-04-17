<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DiscountStrategy: string implements HasLabel
{
    case Same = 'same';
    case Cheapest = 'cheapest';
    case Expensive = 'expensive';
    case  Specific = 'specific';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Same => 'هم‌نوعِ کالای خریداری شده (Same)',
            self::Cheapest => 'ارزان‌ترین کالای موجود در سبد (Cheapest)',
            self::Expensive => 'گران‌ترین کالای موجود در سبد (Expensive)',
            self::Specific => 'یک کالای خاص و مشخص (Specific)',
        };
    }
}

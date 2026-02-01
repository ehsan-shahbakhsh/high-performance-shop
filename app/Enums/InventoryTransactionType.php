<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum InventoryTransactionType: string implements HasLabel, HasColor
{
    case Purchase = 'purchase';
    case Restock = 'restock';
    case Return = 'return';
    case Damage = 'damage';
    case Correction = 'correction';
    case Transfer = 'transfer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Purchase => 'فروش',
            self::Restock => 'شارژ انبار',
            self::Return => 'مرجوعی',
            self::Damage => 'ضایعات/خرابی',
            self::Correction => 'اصلاح موجودی',
            self::Transfer => 'انتقال بین انبار',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Purchase, self::Damage => 'danger',
            self::Restock, self::Return => 'success',
            self::Correction, self::Transfer => 'warning',
        };
    }
}

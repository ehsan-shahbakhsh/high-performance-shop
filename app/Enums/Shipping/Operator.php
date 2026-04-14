<?php

namespace App\Enums\Shipping;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum Operator: string implements HasLabel
{
    case In = 'in';
    case NotIn = 'not_in';
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case GreaterThan = 'greater';
    case GreaterThanOrEquals = 'greater_or_equals';
    case LessThan = 'less';
    case LessThanOrEquals = 'less_or_equals';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::In => 'شامل (In)',
            self::NotIn => 'شامل نباشد (Not In)',
            self::Equals => 'برابر با (=)',
            self::NotEquals => 'نابرابر با (≠)',
            self::GreaterThan => 'بزرگتر از (>)',
            self::GreaterThanOrEquals => 'بزرگتر یا مساوی (≥)',
            self::LessThan => 'کوچکتر از (<)',
            self::LessThanOrEquals => 'کوچکتر یا مساوی (≤)',
        };
    }

    public static function numericOptions(): array
    {
        return collect([
            self::Equals,
            self::NotEquals,
            self::GreaterThan,
            self::GreaterThanOrEquals,
            self::LessThan,
            self::LessThanOrEquals,
        ])->mapWithKeys(static fn(self $case) => [
            $case->value => $case->getLabel()
        ])->toArray();
    }

    public static function relationOptions(): array
    {
        return collect([
            self::In,
            self::NotIn,
            self::Equals,
            self::NotEquals,
        ])->mapWithKeys(static fn(self $case) => [
            $case->value => $case->getLabel()
        ])->toArray();
    }
}

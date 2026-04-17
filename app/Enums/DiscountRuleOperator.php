<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DiscountRuleOperator: string implements HasLabel
{
    case Equals = 'eq';
    case NotEquals = 'neq';
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
    case In = 'in';
    case NotIn = 'not_in';

    public static function numericOperators(): array
    {
        return [
            self::Equals,
            self::NotEquals,
            self::GreaterThan,
            self::GreaterThanOrEqual,
            self::LessThan,
            self::LessThanOrEqual,
        ];
    }

    public static function arrayOperators(): array
    {
        return [
            self::In,
            self::NotIn,
        ];
    }

    public static function booleanOperators(): array
    {
        return [
            self::Equals,
        ];
    }

    public static function equalityOperators(): array
    {
        return [
            self::Equals,
            self::NotEquals,
        ];
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Equals => 'دقیقاً برابر (=)',
            self::NotEquals => 'نامساوی با (!=)',
            self::GreaterThan => 'بیشتر از (>)',
            self::GreaterThanOrEqual => 'حداقل (>=)',
            self::LessThan => 'کمتر از (<=)',
            self::LessThanOrEqual => 'حداکثر (<)',
            self::In => 'شامل این موارد',
            self::NotIn => 'جزء این موارد نباشد',
        };
    }
}

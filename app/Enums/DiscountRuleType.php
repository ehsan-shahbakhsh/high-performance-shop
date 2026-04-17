<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;
use Illuminate\Contracts\Support\Htmlable;

enum DiscountRuleType: string implements HasLabel, HasColor, HasIcon
{
    case CartSubtotal = 'cart_subtotal';
    case CartQuantity = 'cart_quantity';
    case CartWeight = 'cart_weight';

    case CartContainsProduct = 'cart_contains_product';
    case CartContainsCategory = 'cart_contains_category';
    case CartContainsBrand = 'cart_contains_brand';

    case UserId = 'user_id';
//    case UserGroup = 'user_group'; // TODO
    case IsFirstOrder = 'is_first_order';
    case OrderCount = 'order_count';
    case TotalSpent = 'total_spent';

    case ShippingCity = 'shipping_city';
    case ShippingProvince = 'shipping_province';
    case ShippingMethod = 'shipping_method';

    case PaymentMethod = 'payment_method';
    case DayOfWeek = 'day_of_week';
    case TimeRange = 'time_range';

    public function allowedOperators(): array
    {
        $operators = match ($this) {
            self::CartSubtotal,
            self::CartQuantity,
            self::CartWeight,
            self::TotalSpent,
            self::OrderCount => DiscountRuleOperator::numericOperators(),

            self::CartContainsProduct,
            self::CartContainsCategory,
            self::CartContainsBrand,
            self::UserId,
//            self::UserGroup,
            self::ShippingCity,
            self::PaymentMethod => DiscountRuleOperator::arrayOperators(),

            self::IsFirstOrder => DiscountRuleOperator::booleanOperators(),

            self::DayOfWeek => array_merge(DiscountRuleOperator::equalityOperators(), DiscountRuleOperator::arrayOperators()),

            default => [DiscountRuleOperator::Equals],
        };

        return collect($operators)
            ->mapWithKeys(static fn(DiscountRuleOperator $op) => [$op->value => $op->getLabel()])
            ->toArray();
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CartSubtotal => 'مجموع مبلغ سبد خرید (بدون هزینه پستی)',
            self::CartQuantity => 'تعداد کل آیتم‌های سبد',
            self::CartWeight => 'وزن کل مرسوله',

            self::CartContainsProduct => 'پیش‌نیاز: وجود محصول خاص در سبد',
            self::CartContainsCategory => 'پیش‌نیاز: وجود دسته‌بندی خاص در سبد',
            self::CartContainsBrand => 'پیش‌نیاز: وجود برند خاص در سبد',

            self::UserId => 'مشتری خاص (انتخابی)',
//            self::UserGroup => 'گروه مشتریان (User Group)',
            self::IsFirstOrder => 'شرط اولین سفارش (مشتری جدید)',
            self::OrderCount => 'تعداد سفارشات قبلی (وفاداری)',
            self::TotalSpent => 'مجموع خرید تا امروز (VIP)',

            self::ShippingCity => 'شهر محل ارسال',
            self::ShippingProvince => 'استان محل ارسال',
            self::ShippingMethod => 'روش ارسال انتخاب شده',

            self::PaymentMethod => 'روش پرداخت',
            self::DayOfWeek => 'روز هفته (مثلاً دوشنبه‌ها)',
            self::TimeRange => 'بازه زمانی (ساعت خاص)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CartSubtotal,
            self::CartQuantity,
            self::TotalSpent => 'success',

            self::CartContainsProduct,
            self::CartContainsCategory,
            self::CartContainsBrand => 'info',

            self::UserId,
//            self::UserGroup,
            self::OrderCount => 'warning',

            self::IsFirstOrder,
            self::TimeRange => 'danger',

            self::CartWeight,
            self::ShippingCity,
            self::ShippingProvince,
            self::ShippingMethod,
            self::PaymentMethod,
            self::DayOfWeek => 'gray',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::CartSubtotal, self::CartQuantity => 'heroicon-o-shopping-cart',
            self::CartWeight => 'heroicon-o-scale',

            self::CartContainsProduct, self::CartContainsCategory, self::CartContainsBrand => 'heroicon-o-cube',

            self::UserId /*, self::UserGroup*/ => 'heroicon-o-users',
            self::IsFirstOrder => 'heroicon-o-sparkles',
            self::OrderCount, self::TotalSpent => 'heroicon-o-currency-dollar',

            self::ShippingCity, self::ShippingProvince => 'heroicon-o-map-pin',
            self::ShippingMethod => 'heroicon-o-truck',

            self::PaymentMethod => 'heroicon-o-credit-card',
            self::DayOfWeek => 'heroicon-o-calendar',
            self::TimeRange => 'heroicon-o-clock',
        };
    }
}

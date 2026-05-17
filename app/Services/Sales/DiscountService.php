<?php

namespace App\Services\Sales;

use App\Data\Sales\CartDiscountResultData;
use App\Data\Sales\DiscountCalculationResultData;
use App\Enums\DiscountConditionMatchType;
use App\Enums\DiscountRuleOperator;
use App\Enums\DiscountRuleType;
use App\Enums\DiscountScope;
use App\Enums\DiscountStrategy;
use App\Enums\DiscountType;
use App\Enums\Sales\OrderStatus;
use App\Exceptions\BusinessException;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Discount;
use App\Models\DiscountRule;
use App\Models\DiscountUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Sales\Shipping\ShippingService;
use Illuminate\Support\Collection;

class DiscountService
{
    protected function collectCandidateDiscounts(?Coupon $coupon = null): Collection
    {
        return Discount::query()
            ->where(static function ($query) use ($coupon) {
                $query->where('is_automatic', true);

                if ($coupon !== null) {
                    $query->orWhere('id', $coupon->discount_id);
                }
            })
            ->where('is_active', true)
            ->where(static function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(static function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('priority')
            ->get();
    }

    public function isEligible(Discount $discount, Cart $cart, ?Coupon $coupon = null): bool
    {
        if (! $discount->is_active || ! $coupon->is_active) {
            return false;
        }

        if ($coupon->discount_id !== $discount->id) {
            return false;
        }

        if (
            ($discount->starts_at && $discount->starts_at->isFuture()) ||
            ($discount->ends_at && $discount->ends_at->isPast())
        ) {
            return false;
        }

        if ($coupon && $coupon->expires_at && $coupon->expires_at->isPast()) {
            return false;
        }

        if ($discount->usage_limit && $discount->used >= $discount->usage_limit) {
            return false;
        }

        if (! $this->meetsConditions($cart, $discount)) {
            return false;
        }

        return true;
    }

    public function meetsConditions(Cart $cart, Discount $discount): bool
    {
        $discount->loadMissing('rules');

        $rules = $discount->rules;

        if ($rules->isEmpty()) {
            return true;
        }

        return match ($discount->condition_match_type) {
            DiscountConditionMatchType::All => $this->passesAll($cart, $rules),
            DiscountConditionMatchType::Any => $this->passesAny($cart, $rules),
        };
    }

    protected function passesAll(Cart $cart, Collection $rules): bool
    {
        foreach ($rules as $rule) {
            if (! $this->evaluateRule($cart, $rule)) {
                return false;
            }
        }

        return true;
    }

    protected function passesAny(Cart $cart, Collection $rules): bool
    {
        foreach ($rules as $rule) {
            if ($this->evaluateRule($cart, $rule)) {
                return true;
            }
        }

        return false;
    }

    protected function evaluateRule(Cart $cart, DiscountRule $rule): bool
    {
        switch ($rule->type) {
            case DiscountRuleType::CartSubtotal:
                $cartValue = $cart->items()
                    ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
                    ->selectRaw('SUM(v.price * cart_items.quantity) as subtotal')
                    ->value('subtotal') ?? 0;

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_integer);
            case DiscountRuleType::CartQuantity:
                $cartValue = $cart->items()
                    ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
                    ->selectRaw('SUM(cart_items.quantity) as items_qty_sum')
                    ->value('items_qty_sum') ?? 0;

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_integer);
            case DiscountRuleType::CartWeight:
                $cartValue = $cart->items()
                    ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
                    ->whereNotNull('v.weight')
                    ->selectRaw('SUM(v.weight * cart_items.quantity) as items_weight_sum')
                    ->value('items_weight_sum') ?? 0;

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_integer);
            case DiscountRuleType::CartContainsProduct:
                $cartValue = $cart->items()
                    ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
                    ->join('products as p', 'p.id', '=', 'v.product_id')
                    ->whereNull('v.deleted_at')
                    ->whereNull('p.deleted_at')
                    ->distinct()
                    ->pluck('v.product_id')
                    ->toArray();

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_json);
            case DiscountRuleType::CartContainsCategory:
                $cartValue = $cart->items()
                    ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
                    ->join('product_product_category as pc', 'pc.product_id', '=', 'v.product_id')
                    ->whereNull('v.deleted_at')
                    ->distinct()
                    ->pluck('pc.product_category_id')
                    ->toArray();

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_json);
            case DiscountRuleType::CartContainsBrand:
                $cartValue = $cart->items()
                    ->join('product_variants as pv', 'pv.id', '=', 'cart_items.product_variant_id')
                    ->join('products as p', 'p.id', '=', 'pv.product_id')
                    ->whereNotNull('p.brand_id')
                    ->whereNull('v.deleted_at')
                    ->whereNull('p.deleted_at')
                    ->distinct()
                    ->pluck('p.brand_id')
                    ->toArray();

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_json);
            case DiscountRuleType::UserId:
                $cartValue = [$cart->user_id];

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_json);
            case DiscountRuleType::IsFirstOrder:
                $ordersCount = Order::where('user_id', $cart->user_id)
                    ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
                    ->count();

                $cartValue = $ordersCount === 0;

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_boolean);
            case DiscountRuleType::OrderCount:
                $ordersCount = Order::where('user_id', $cart->user_id)
                    ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
                    ->count();

                $cartValue = $ordersCount;

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_integer);
            case DiscountRuleType::TotalSpent:
                $cartValue = Order::where('user_id', $cart->user_id)
                    ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
                    ->sum('grand_total');

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_integer);
            case DiscountRuleType::ShippingProvince:
                if (blank($cart->shipping_address_id)) {
                    return false;
                }

                $cartValue = [$cart->shippingAddress->province_id];

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_json);
            case DiscountRuleType::ShippingCity:
                if (blank($cart->shipping_address_id)) {
                    return false;
                }

                $cartValue = [$cart->shippingAddress->city_id];

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_json);
            case DiscountRuleType::ShippingMethod:
                if (blank($cart->shipping_method_id)) {
                    return false;
                }

                $cartValue = [$cart->shipping_method_id];

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_json);
            case DiscountRuleType::DayOfWeek:
                $cartValue = [verta()->dayOfWeek];

                return $this->evaluateRuleOperator($cartValue, $rule->operator, $rule->value_json);
            case DiscountRuleType::TimeRange:
                return $this->evaluateTimeRangeRule($rule);
            default:
                return false;
        }
    }

    protected function evaluateTimeRangeRule(DiscountRule $rule): bool
    {
        $range = $rule->value_json;

        if (! isset($range['start'], $range['end'])) {
            return false;
        }

        $now = now();
        $start = today()->setTimeFromTimeString($range['start']);
        $end = today()->setTimeFromTimeString($range['end']);

        if ($start->lte($end)) {
            return $now->between($start, $end);
        }

        return $now->gte($start) || $now->lte($end);
    }

    protected function evaluateRuleOperator(mixed $cartValue, DiscountRuleOperator $operator, mixed $ruleValue): bool
    {
        return match ($operator) {
            DiscountRuleOperator::Equals => $cartValue == $ruleValue,
            DiscountRuleOperator::NotEquals => $cartValue != $ruleValue,
            DiscountRuleOperator::GreaterThan => $cartValue > $ruleValue,
            DiscountRuleOperator::GreaterThanOrEqual => $cartValue >= $ruleValue,
            DiscountRuleOperator::LessThan => $cartValue < $ruleValue,
            DiscountRuleOperator::LessThanOrEqual => $cartValue <= $ruleValue,
            DiscountRuleOperator::In => count(array_intersect((array) $cartValue, (array) $ruleValue)) > 0,
            DiscountRuleOperator::NotIn => count(array_intersect((array) $cartValue, (array) $ruleValue)) === 0,
        };
    }

    /**
     * @throws BusinessException
     */
    public function calculateDiscountsAmount(Cart $cart, Collection $discounts): CartDiscountResultData
    {
        $cartResult = new CartDiscountResultData;

        foreach ($discounts as $discount) {
            $singleResult = $this->calculateDiscountValueForCart($cart, $discount);

            if ($singleResult->totalAmount > 0) {
                $cartResult->totalDiscountAmount += $singleResult->totalAmount;
                $cartResult->shippingDiscountAmount += $singleResult->shippingAmount;
                $cartResult->appliedDiscounts[] = $discount->id;

                foreach ($singleResult->itemDiscounts as $itemId => $amount) {
                    $cartResult->itemDiscounts[$itemId] =
                        ($cartResult->itemDiscounts[$itemId] ?? 0) + $amount;
                }

                $cartResult->discountBreakdowns[$discount->id] = $singleResult;
            }
        }

        return $cartResult;
    }

    /**
     * @throws BusinessException
     */
    public function evaluateBestDiscountScenario(Cart $cart, ?Coupon $coupon = null): CartDiscountResultData
    {
        $candidates = $this->collectCandidateDiscounts($coupon);

        $eligibleDiscounts = $candidates->filter(function (Discount $discount) use ($cart, $coupon) {
            return $this->isEligible($discount, $cart, $coupon);
        });

        if ($eligibleDiscounts->isEmpty()) {
            return new CartDiscountResultData;
        }

        $exclusiveDiscounts = $eligibleDiscounts->where('is_exclusive', true);
        $normalDiscounts = $eligibleDiscounts->where('is_exclusive', false);

        if ($exclusiveDiscounts->isEmpty()) {
            return $this->calculateDiscountsAmount($cart, $normalDiscounts);
        }

        $normalResult = clone $this->calculateDiscountsAmount($cart, $normalDiscounts);

        $bestExclusiveResult = new CartDiscountResultData;

        foreach ($exclusiveDiscounts as $exclusive) {
            $exclusiveResult = clone $this->calculateDiscountsAmount($cart, collect([$exclusive]));

            if ($exclusiveResult->totalDiscountAmount > $bestExclusiveResult->totalDiscountAmount) {
                $bestExclusiveResult = $exclusiveResult;
            }
        }

        if ($normalResult->totalDiscountAmount > $bestExclusiveResult->totalDiscountAmount) {
            return $normalResult;
        }

        return $bestExclusiveResult;
    }

    /**
     * @throws BusinessException
     */
    protected function calculateDiscountValueForCart(Cart $cart, Discount $discount): DiscountCalculationResultData
    {
        return match ($discount->scope) {
            DiscountScope::Order => $this->calculateOrderDiscount($cart, $discount),
            DiscountScope::Shipping => $this->calculateShippingDiscount($cart, $discount),
            DiscountScope::Item => $this->calculateItemDiscount($cart, $discount),
        };
    }

    protected function calculateOrderDiscount(Cart $cart, Discount $discount): DiscountCalculationResultData
    {
        $subtotal = $cart->subtotal;

        if ($discount->type === DiscountType::Fixed) {
            return new DiscountCalculationResultData(totalAmount: min($discount->amount, $subtotal));
        }

        if ($discount->type === DiscountType::Percentage) {
            $discountAmount = (int) round(
                $subtotal * ($discount->amount / 100)
            );

            if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
                return new DiscountCalculationResultData(totalAmount: $discount->max_discount_amount);
            }

            return new DiscountCalculationResultData(totalAmount: $discountAmount);
        }

        return new DiscountCalculationResultData;
    }

    /**
     * @throws BusinessException
     */
    protected function calculateShippingDiscount(Cart $cart, Discount $discount): DiscountCalculationResultData
    {
        if (is_null($cart->shipping_method_id) || is_null($cart->shipping_address_id)) {
            return new DiscountCalculationResultData;
        }

        $shippingMethod = $cart->shippingMethod;
        $address = $cart->shippingAddress;

        $shippingService = resolve(ShippingService::class);
        $baseShippingCost = $shippingService->calculateMethodPrice($shippingMethod, $cart, $address);

        if ($baseShippingCost === 0) {
            return new DiscountCalculationResultData;
        }

        if ($discount->type === DiscountType::Fixed) {
            $discountAmount = $discount->amount;
        } elseif ($discount->type === DiscountType::FreeShipping) {
            $discountAmount = $baseShippingCost;
        } else {
            $discountAmount = ($baseShippingCost * $discount->amount) / 100;
        }

        $discountAmount = min((int) $discountAmount, $baseShippingCost);

        return new DiscountCalculationResultData(
            totalAmount: $discountAmount,
            shippingAmount: $discountAmount,
        );
    }

    protected function calculateItemDiscount(Cart $cart, Discount $discount): DiscountCalculationResultData
    {
        $cart->loadMissing('items.variant.product.categories:id');
        $discount->loadMissing('discountables');

        if ($discount->discountables->isEmpty()) {
            $eligibleCartItems = $cart->items;
        } else {
            $targets = [
                Product::class => ['include' => [], 'exclude' => []],
                ProductCategory::class => ['include' => [], 'exclude' => []],
                Brand::class => ['include' => [], 'exclude' => []],
            ];

            foreach ($discount->discountables as $d) {
                $type = $d->discountable_type;
                $mode = $d->is_excluded ? 'exclude' : 'include';

                $targets[$type][$mode][$d->discountable_id] = true;
            }

            $eligibleCartItems = $cart->items->filter(function (CartItem $item) use ($targets) {
                $product = $item->variant->product;

                if (isset($targets[Product::class]['exclude'][$product->id])) {
                    return false;
                }

                if (isset($targets[Brand::class]['exclude'][$product->brand_id])) {
                    return false;
                }

                $categoryIds = $product->categories->pluck('id')->toArray();

                foreach ($categoryIds as $categoryId) {
                    if (isset($targets[ProductCategory::class]['exclude'][$categoryId])) {
                        return false;
                    }
                }

                $hasIncludes =
                    ! empty($targets[Product::class]['include']) ||
                    ! empty($targets[Brand::class]['include']) ||
                    ! empty($targets[ProductCategory::class]['include']);

                if (! $hasIncludes) {
                    return true;
                }

                if (
                    isset($targets[Product::class]['include'][$product->id]) ||
                    isset($targets[Brand::class]['include'][$product->brand_id])
                ) {
                    return true;
                }

                foreach ($categoryIds as $categoryId) {
                    if (isset($targets[ProductCategory::class]['include'][$categoryId])) {
                        return true;
                    }
                }

                return false;
            });
        }

        $discountResult = new DiscountCalculationResultData;

        if ($discount->type === DiscountType::BuyXGetY) {
            $settings = $discount->action_settings?->bogo;
            $buyQty = $settings?->buyQty;
            $getQty = $settings?->getQty;
            $discountPercent = $settings?->discountPercent;
            $strategy = $settings?->strategy;
            $maxApplications = $settings?->maxApplicationsPerOrder;

            $flattenedItems = collect();
            foreach ($eligibleCartItems as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $flattenedItems->push([
                        'cart_item_id' => $item->id,
                        'variant_id' => $item->product_variant_id,
                        'unit_price' => $item->variant->final_price,
                    ]);
                }
            }

            $applications = 0;
            $rewardItems = collect();

            if ($strategy === DiscountStrategy::Specific) {
                $applications = (int) floor($flattenedItems->count() / $buyQty);

                if ($maxApplications) {
                    $applications = min($applications, $maxApplications);
                }

                $targetVariantId = $settings->targetVariantId;
                $allowedRewardQty = $applications * $getQty;

                $flattenedTargets = collect();
                foreach ($cart->items as $item) {
                    if ($item->product_variant_id === $targetVariantId) {
                        for ($i = 0; $i < $item->quantity; $i++) {
                            $flattenedTargets->push([
                                'cart_item_id' => $item->id,
                                'unit_price' => $item->variant->final_price,
                            ]);
                        }
                    }
                }

                $rewardItems = $flattenedTargets->take($allowedRewardQty);
            } elseif ($strategy === DiscountStrategy::Same) {
                $groupedItems = $flattenedItems->groupBy('variant_id');

                foreach ($groupedItems as $variantItems) {
                    $groupApplications = (int) floor($variantItems->count() / ($buyQty + $getQty));

                    if ($maxApplications) {
                        if ($applications >= $maxApplications) {
                            break;
                        }
                        $groupApplications = min($groupApplications, $maxApplications - $applications);
                    }

                    if ($groupApplications > 0) {
                        $applications += $groupApplications;
                        $rewardItems = $rewardItems->merge($variantItems->take($groupApplications * $getQty));
                    }
                }
            } else {
                $applications = (int) floor($flattenedItems->count() / ($buyQty + $getQty));

                if ($maxApplications) {
                    $applications = min($applications, $maxApplications);
                }

                $allowedRewardQty = $applications * $getQty;

                if ($strategy === DiscountStrategy::Cheapest) {
                    $rewardItems = $flattenedItems->sortBy('unit_price')->take($allowedRewardQty);
                } elseif ($strategy === DiscountStrategy::Expensive) {
                    $rewardItems = $flattenedItems->sortByDesc('unit_price')->take($allowedRewardQty);
                }
            }

            foreach ($rewardItems as $reward) {
                $itemDiscount = (int) (($reward['unit_price'] * $discountPercent) / 100);

                if (! isset($discountResult->itemDiscounts[$reward['cart_item_id']])) {
                    $discountResult->itemDiscounts[$reward['cart_item_id']] = 0;
                }

                $discountResult->itemDiscounts[$reward['cart_item_id']] += $itemDiscount;
                $discountResult->totalAmount += $itemDiscount;
            }
        } elseif (in_array($discount->type, [DiscountType::Fixed, DiscountType::Percentage])) {
            $settings = $discount->action_settings?->item;
            $strategy = $settings?->strategy;
            $maxApplications = $settings?->maxApplicationsPerOrder;

            $flattenedItems = collect();
            foreach ($eligibleCartItems as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $flattenedItems->push([
                        'cart_item_id' => $item->id,
                        'variant_id' => $item->product_variant_id,
                        'unit_price' => $item->variant->final_price,
                    ]);
                }
            }

            if ($strategy === DiscountStrategy::Specific && $settings?->targetVariantId) {
                $flattenedItems = $flattenedItems->where('variant_id', $settings->targetVariantId);
            }

            if ($strategy === DiscountStrategy::Cheapest) {
                $flattenedItems = $flattenedItems->sortBy('unit_price');
            } elseif ($strategy === DiscountStrategy::Expensive) {
                $flattenedItems = $flattenedItems->sortByDesc('unit_price');
            }

            if ($maxApplications !== null) {
                $flattenedItems = $flattenedItems->take($maxApplications);
            }

            foreach ($flattenedItems as $flatItem) {
                $price = $flatItem['unit_price'];
                $itemDiscount = 0;

                if ($discount->type === DiscountType::Fixed) {
                    $itemDiscount = $discount->amount;
                } elseif ($discount->type === DiscountType::Percentage) {
                    $itemDiscount = (int) (($price * $discount->amount) / 100);
                }

                $itemDiscount = min($itemDiscount, $price);

                if (! isset($discountResult->itemDiscounts[$flatItem['cart_item_id']])) {
                    $discountResult->itemDiscounts[$flatItem['cart_item_id']] = 0;
                }

                $discountResult->itemDiscounts[$flatItem['cart_item_id']] += $itemDiscount;
                $discountResult->totalAmount += $itemDiscount;
            }

            if ($discount->type === DiscountType::Percentage && $discount->max_discount_amount) {
                $discountResult->totalAmount = min($discountResult->totalAmount, (int) $discount->max_discount_amount);
            }

            $itemsTotal = array_sum(array_values($discountResult->itemDiscounts));

            if ($itemsTotal > $discountResult->totalAmount) {
                $ratio = $discountResult->totalAmount / $itemsTotal;
                $newItemsTotal = 0;

                foreach ($discountResult->itemDiscounts as $itemId => $rawDiscount) {
                    $scaledDiscount = (int) floor($rawDiscount * $ratio);
                    $discountResult->itemDiscounts[$itemId] = $scaledDiscount;
                    $newItemsTotal += $scaledDiscount;
                }

                $diff = $discountResult->totalAmount - $newItemsTotal;

                if ($diff > 0) {
                    foreach ($discountResult->itemDiscounts as $itemId => &$currentDiscount) {
                        if ($diff <= 0) {
                            break;
                        }

                        $flatItem = collect($flattenedItems)->firstWhere('cart_item_id', $itemId);
                        $itemPrice = $flatItem['unit_price'];

                        $availableGap = $itemPrice - $currentDiscount;

                        if ($availableGap > 0) {
                            $amountToAdd = min($diff, $availableGap);
                            $currentDiscount += $amountToAdd;
                            $diff -= $amountToAdd;
                        }
                    }
                }
            }
        }

        return $discountResult;
    }
}

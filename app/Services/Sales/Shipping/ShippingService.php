<?php

namespace App\Services\Sales\Shipping;

use App\Data\Shipping\ShippingRuleData;
use App\Exceptions\BusinessException;
use App\Enums\Shipping\{ConditionType, MatchType, Operator};
use App\Enums\ShippingZoneLocationType;
use App\Models\{Address, Cart, ShippingMethod, ShippingRate, ShippingZone};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\{Arr, Collection};
use Spatie\LaravelData\DataCollection;

class ShippingService
{
    public function getZonesForAddress(Address $address): Collection
    {
        return ShippingZone::query()
            ->where('is_active', true)
            ->whereDoesntHave('locations', static function (Builder $q) use ($address) {
                $q->where('type', ShippingZoneLocationType::Exclude)
                    ->where(static function ($q) use ($address) {
                        $q->where(static function ($q) use ($address) {
                            $q->where('province_id', $address->province_id)
                                ->orWhereNull('province_id');
                        })
                            ->where(static function ($q) use ($address) {
                                $q->where('city_id', $address->city_id)
                                    ->orWhereNull('city_id');
                            });
                    });
            })
            ->whereHas('locations', static function (Builder $q) use ($address) {
                $q->where('type', ShippingZoneLocationType::Include)
                    ->where(static function ($q) use ($address) {
                        $q->where(static function ($q) use ($address) {
                            $q->where('province_id', $address->province_id)
                                ->orWhereNull('province_id');
                        })
                            ->where(static function ($q) use ($address) {
                                $q->where('city_id', $address->city_id)
                                    ->orWhereNull('city_id');
                            });
                    });
            })
            ->orderBy('position')
            ->get();
    }

    public function getMethodsForZones(Collection $zones, int $cartTotalWeight): Collection
    {
        if ($zones->isEmpty()) {
            return collect();
        }

        $zoneIds = $zones->pluck('id');

        return ShippingMethod::query()
            ->with([
                'rates' => static fn($query) => $query
                    ->orderBy('position')
                    ->whereIn('shipping_zone_id', $zoneIds)
                    ->where('is_active', true),
            ])
            ->where('is_active', true)
            ->whereRelation('carrier', 'is_active', true)
            ->where(static function (Builder $query) use ($cartTotalWeight) {
                $query->whereNull('max_weight')
                    ->orWhere(static function (Builder $query) use ($cartTotalWeight) {
                        $query->whereNotNull('max_weight')
                            ->where('max_weight', '>=', $cartTotalWeight);
                    });
            })
            ->whereHas('rates', static function ($query) use ($zoneIds) {
                $query->whereIn('shipping_zone_id', $zoneIds)
                    ->where('is_active', true);
            })
            ->orderBy('position')
            ->get();
    }

    public function getAvailableMethods(Address $address, Cart $cart): Collection
    {
        $cartTotalWeight = $cart->total_weight;

        $zones = $this->getZonesForAddress($address);

        $methods = $this->getMethodsForZones($zones, $cartTotalWeight);

        return $methods->filter(function ($method) use ($cart, $cartTotalWeight) {
            return $method->rates->contains(fn($rate) => $this->isRateValidForCart($rate, $cart, $cartTotalWeight));
        });
    }

    public function isSupportedForAddress(ShippingMethod $method, Address $address, int $cartTotalWeight): bool
    {
        $method->loadMissing('carrier');

        $availableZones = $this->getZonesForAddress($address);

        if (!$method->is_active || !$method->carrier->is_active || $availableZones->isEmpty()) {
            return false;
        }

        $zoneIds = $availableZones->pluck('id');

        return ShippingMethod::query()
            ->whereKey($method->id)
            ->where(static function (Builder $query) use ($cartTotalWeight) {
                $query->whereNull('max_weight')
                    ->orWhere('max_weight', '>=', $cartTotalWeight);
            })
            ->whereHas('rates', static function ($query) use ($zoneIds) {
                $query->whereIn('shipping_zone_id', $zoneIds)
                    ->where('is_active', true);
            })
            ->exists();
    }

    /**
     * @throws BusinessException
     */
    public function calculateMethodPrice(ShippingMethod $method, Cart $cart, Address $address, bool $isCod = false): int
    {
        $availableZones = $this->getZonesForAddress($address);

        $rate = $method->rates()
            ->where('is_active', true)
            ->whereIn('shipping_zone_id', $availableZones->pluck('id'))
            ->orderBy('position')
            ->first();

        if (!$rate) {
            throw new BusinessException("متاسفانه سرویس {$method->name} برای آدرس شما فعال نیست.");
        }

        if ($rate->free_shipping_over && $cart->subtotal >= $rate->free_shipping_over) {
            return 0;
        }

        $totalPrice = $rate->base_price;

        if ($rate->price_per_kg) {
            $weightInKg = ceil($cart->total_weight / 1000);
            $totalPrice += ($weightInKg * $rate->price_per_kg);
        }

        if ($isCod && $method->is_cod_supported) {
            $totalPrice += $rate->cod_fee ?? 0;
        }

        return (int)$totalPrice;
    }

    protected function isRateValidForCart(ShippingRate $rate, Cart $cart, int $cartTotalWeight): bool
    {
        if ($rate->min_weight !== null && $rate->min_weight > $cartTotalWeight) {
            return false;
        }

        if ($rate->max_weight !== null && $rate->max_weight < $cartTotalWeight) {
            return false;
        }

        if ($rate->min_subtotal !== null && $rate->min_subtotal > $cart->subtotal) {
            return false;
        }

        if ($rate->max_subtotal !== null && $rate->max_subtotal < $cart->subtotal) {
            return false;
        }

        return $this->meetsConditions($cart, $rate);
    }

    protected function meetsConditions(Cart $cart, ShippingRate $rate): bool
    {
        $rules = $rate->conditions->rules;

        if ($rules->count() == 0) {
            return true;
        }

        return match ($rate->conditions->matchType) {
            MatchType::All => $this->passesAll($cart, $rules),
            MatchType::Any => $this->passesAny($cart, $rules),
        };
    }

    protected function passesAll(Cart $cart, DataCollection $rules): bool
    {
        foreach ($rules as $rule) {
            if (!$this->evaluateRule($cart, $rule)) {
                return false;
            }
        }
        return true;
    }

    protected function passesAny(Cart $cart, DataCollection $rules): bool
    {
        foreach ($rules as $rule) {
            if ($this->evaluateRule($cart, $rule)) {
                return true;
            }
        }
        return false;
    }

    protected function evaluateRule(Cart $cart, ShippingRuleData $rule): bool
    {
        if ($rule->type === ConditionType::Categories) {
            $cartValue = $cart->items()
                ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
                ->join('product_product_category as pc', 'pc.product_id', '=', 'v.product_id')
                ->distinct()
                ->pluck('pc.product_category_id')
                ->toArray();
            $ruleValue = Arr::wrap($rule->valueIds ?? $rule->valueId);
        } else if ($rule->type === ConditionType::Products) {
            $cartValue = $cart->items()
                ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
                ->distinct()
                ->pluck('v.product_id')
                ->toArray();
            $ruleValue = Arr::wrap($rule->valueIds ?? $rule->valueId);
        } else if ($rule->type === ConditionType::Brands) {
            $cartValue = $cart->items()
                ->join('product_variants as pv', 'pv.id', '=', 'cart_items.product_variant_id')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->whereNotNull('p.brand_id')
                ->distinct()
                ->pluck('p.brand_id')
                ->toArray();
            $ruleValue = Arr::wrap($rule->valueIds ?? $rule->valueId);
        } else {
            $cartValue = $cart->items_count;
            $ruleValue = $rule->value;
        }

        return $this->evaluateRuleOperator($cartValue, $rule->operator, $ruleValue);
    }

    protected function evaluateRuleOperator(mixed $cartValue, Operator $operator, mixed $ruleValue): bool
    {
        return match ($operator) {
            Operator::Equals => $cartValue == $ruleValue,
            Operator::NotEquals => $cartValue != $ruleValue,
            Operator::GreaterThan => $cartValue > $ruleValue,
            Operator::GreaterThanOrEquals => $cartValue >= $ruleValue,
            Operator::LessThan => $cartValue < $ruleValue,
            Operator::LessThanOrEquals => $cartValue <= $ruleValue,
            Operator::In => count(array_intersect((array)$cartValue, (array)$ruleValue)) > 0,
            Operator::NotIn => count(array_intersect((array)$cartValue, (array)$ruleValue)) === 0,
        };
    }
}

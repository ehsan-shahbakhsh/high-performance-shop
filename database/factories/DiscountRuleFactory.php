<?php

namespace Database\Factories;

use App\Enums\DiscountRuleType;
use App\Models\Discount;
use App\Models\DiscountRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountRule>
 */
class DiscountRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(DiscountRuleType::cases());

        $allowedOperators = array_keys($type->allowedOperators());
        $operator = fake()->randomElement($allowedOperators);

        $valueString = null;
        $valueInteger = null;
        $valueFloat = null;
        $valueBoolean = null;
        $valueJson = null;

        switch ($type) {
            case DiscountRuleType::CartSubtotal:
            case DiscountRuleType::TotalSpent:
                $valueInteger = fake()->numberBetween(100_000, 5_000_000);
                break;

            case DiscountRuleType::CartQuantity:
            case DiscountRuleType::OrderCount:
                $valueInteger = fake()->numberBetween(1, 10);
                break;

            case DiscountRuleType::CartWeight:
                $valueInteger = fake()->numberBetween(500, 5000);
                break;

            case DiscountRuleType::CartContainsProduct:
            case DiscountRuleType::CartContainsCategory:
            case DiscountRuleType::CartContainsBrand:
            case DiscountRuleType::UserId:
                $valueJson = [fake()->numberBetween(1, 15), fake()->numberBetween(16, 30)];
                break;

//            DiscountRuleType::UserGroup => fake()->randomElements(['VIP', 'B2B', 'Regular'], fake()->numberBetween(1, 2)),

            case DiscountRuleType::ShippingCity:
            case DiscountRuleType::ShippingProvince:
                $valueJson = fake()->randomElements(['تهران', 'اصفهان', 'مشهد', 'شیراز', 'تبریز'], fake()->numberBetween(1, 3));
                break;

            case DiscountRuleType::ShippingMethod:
                $valueString = fake()->randomElement(['post', 'tipax', 'peyk']);
                break;

            case DiscountRuleType::PaymentMethod:
                $valueString = fake()->randomElement(['online', 'cod', 'wallet']);
                break;

            case DiscountRuleType::DayOfWeek:
                $valueJson = fake()->randomElements([1, 2, 3, 4, 5, 6, 7], fake()->numberBetween(1, 3));
                break;

            case DiscountRuleType::IsFirstOrder:
                $valueBoolean = fake()->boolean();
                break;

            case DiscountRuleType::TimeRange:
                $valueJson = ['start' => fake()->time('H:i'), 'end' => fake()->time('H:i')];
                break;
        }

        return [
            'discount_id' => Discount::factory(),
            'type' => $type,
            'operator' => $operator,
            'value_string' => $valueString,
            'value_integer' => $valueInteger,
            'value_float' => $valueFloat,
            'value_boolean' => $valueBoolean,
            'value_json' => $valueJson,
        ];
    }
}

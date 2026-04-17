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

        $value = match ($type) {
            DiscountRuleType::CartSubtotal,
            DiscountRuleType::TotalSpent => fake()->numberBetween(100_000, 5_000_000),

            DiscountRuleType::CartQuantity,
            DiscountRuleType::OrderCount => fake()->numberBetween(1, 10),

            DiscountRuleType::CartWeight => fake()->numberBetween(500, 5000),

            DiscountRuleType::CartContainsProduct,
            DiscountRuleType::CartContainsCategory,
            DiscountRuleType::CartContainsBrand,
            DiscountRuleType::UserId => [fake()->numberBetween(1, 15), fake()->numberBetween(16, 30)],

//            DiscountRuleType::UserGroup => fake()->randomElements(['VIP', 'B2B', 'Regular'], fake()->numberBetween(1, 2)),

            DiscountRuleType::ShippingCity,
            DiscountRuleType::ShippingProvince => fake()->randomElements(['تهران', 'اصفهان', 'مشهد', 'شیراز', 'تبریز'], fake()->numberBetween(1, 3)),

            DiscountRuleType::ShippingMethod => fake()->randomElement(['post', 'tipax', 'peyk']),
            DiscountRuleType::PaymentMethod => fake()->randomElement(['online', 'cod', 'wallet']),

            DiscountRuleType::DayOfWeek => fake()->randomElements([1, 2, 3, 4, 5, 6, 7], fake()->numberBetween(1, 3)),

            DiscountRuleType::IsFirstOrder => fake()->boolean(),

            DiscountRuleType::TimeRange => fake()->time('H:i') . '-' . fake()->time('H:i'),
        };

        return [
            'discount_id' => Discount::factory(),
            'type' => $type,
            'operator' => $operator,
            'value' => $value,
        ];
    }
}

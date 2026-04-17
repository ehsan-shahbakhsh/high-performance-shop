<?php

namespace Database\Factories;

use App\Enums\{DiscountConditionMatchType, DiscountScope, DiscountStrategy, DiscountType};
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(DiscountType::cases());
        $isPercentage = $type === DiscountType::Percentage;

        $amount = $isPercentage
            ? fake()->numberBetween(5, 80)
            : fake()->numberBetween(10_000, 500_000);

        $maxDiscountAmount = $isPercentage
            ? fake()->optional(0.6)->numberBetween(50_000, 500_000)
            : null;

        $startsAt = fake()->optional()->dateTimeBetween('-1 month', '+1 week');
        $endsAt = fake()->optional()->dateTimeBetween($startsAt, '+2 months');

        $actionSettings = $this->generateActionSettings($type);

        return [
            'name' => fake()->randomElement(['جشنواره ', 'تخفیف ویژه ', 'کمپین ']) . fake()->word() . ' ' . fake()->numberBetween(1400, 1405),

            'is_automatic' => fake()->boolean(30),

            'type' => $type->value,
            'scope' => fake()->randomElement(DiscountScope::cases()),

            'amount' => $amount,
            'max_discount_amount' => $maxDiscountAmount,

            'starts_at' => $startsAt,
            'ends_at' => $endsAt,

            'action_settings' => $actionSettings,

            'condition_match_type' => fake()->randomElement(DiscountConditionMatchType::cases()),

            'is_exclusive' => fake()->boolean(20),
            'is_active' => fake()->boolean(85),

            'priority' => fake()->numberBetween(0, 100),

            'usage_limit' => fake()->optional(0.7)->numberBetween(10, 500),
            'user_usage_limit' => fake()->optional(0.8)->numberBetween(1, 3),
        ];
    }

    private function generateActionSettings(DiscountType $type): array
    {
        $strategy = fake()->randomElement(DiscountStrategy::cases());

        if ($type === DiscountType::BuyXGetY) {
            return [
                'bogo' => [
                    'buy_qty' => fake()->numberBetween(1, 3),
                    'get_qty' => fake()->numberBetween(1, 2),
                    'discount_percent' => fake()->randomElement([50, 100]),
                    'strategy' => $strategy->value,
                    'target_variant_id' => $strategy === DiscountStrategy::Specific ? fake()->numberBetween(1, 100) : null,
                    'max_applications_per_order' => fake()->optional()->numberBetween(1, 3),
                ],
                'item' => null,
            ];
        }

        return [
            'bogo' => null,
            'item' => [
                'strategy' => $strategy->value,
                'max_applications_per_order' => fake()->optional()->numberBetween(1, 5),
            ],
        ];
    }
}

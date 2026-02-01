<?php

namespace Database\Factories;

use App\Enums\InventoryTransactionType;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(-50, 50);
        if ($quantity === 0) $quantity = 1;

        $quantityBefore = fake()->numberBetween(50, 500);

        return [
            'inventory_id' => Inventory::factory(),
            'user_id' => User::factory(),

            'type' => fake()->randomElement(InventoryTransactionType::cases()),

            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore + $quantity,

            'reason' => $this->faker->sentence(),

            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}

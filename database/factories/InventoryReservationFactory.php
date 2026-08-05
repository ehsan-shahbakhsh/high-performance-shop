<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryReservation>
 */
class InventoryReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_id' => Inventory::factory(),
            'order_id' => Order::factory(),

            'quantity' => fake()->numberBetween(1, 10),

            'expires_at' => now()->addMinutes(30),
            'released_at' => null,
            'consumed_at' => null,
        ];
    }

    public function released(): static
    {
        return $this->state(fn () => [
            'released_at' => now()->subMinutes(10),
            'consumed_at' => null,
        ]);
    }

    public function consumed(): static
    {
        return $this->state(fn () => [
            'consumed_at' => now()->subMinutes(10),
            'released_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(10),
            'released_at' => null,
            'consumed_at' => null,
        ]);
    }
}

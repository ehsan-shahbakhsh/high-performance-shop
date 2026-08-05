<?php

namespace Database\Factories;

use App\Enums\Sales\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $oldStatus = fake()->boolean(70)
            ? fake()->randomElement(OrderStatus::cases())
            : null;

        $newStatus = fake()->randomElement(
            collect(OrderStatus::cases())
                ->reject(fn ($status) => $status === $oldStatus)
                ->values()
                ->all()
        );

        return [
            'order_id' => Order::factory(),

            'old_status' => $oldStatus,
            'new_status' => $newStatus,

            'user_id' => fake()->boolean(80) ? User::factory() : null,

            'comment' => fake()->boolean() ? fake()->sentence() : null,
            'customer_notified' => fake()->boolean(),

            'metadata' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Data\Sales\TransactionGatewayPayloadData;
use App\Enums\Sales\TransactionStatus;
use App\Enums\Sales\TransactionType;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(TransactionStatus::cases());
        $isSuccess = $status === TransactionStatus::Successful;

        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'payment_method_id' => PaymentMethod::factory(),

            'type' => fake()->randomElement(TransactionType::cases()),
            'amount' => fake()->numberBetween(10, 5000) * 1000,
            'status' => $status,

            'token' => fake()->boolean(80) ? Str::uuid()->toString() : null,
            'reference_id' => $isSuccess ? fake()->numerify('##########') : null,

            'gateway_payload' => new TransactionGatewayPayloadData(
                request: null,
                callback: null,
                verify: null,
            ),

            'paid_at' => $isSuccess ? now()->subMinutes(fake()->numberBetween(1, 120)) : null,
            'refunded_at' => null,

            'description' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }
}

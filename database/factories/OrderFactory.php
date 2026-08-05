<?php

namespace Database\Factories;

use App\Enums\Sales\OrderPaymentStatus;
use App\Enums\Sales\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        do {
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        $itemsSubtotal = fake()->numberBetween(10, 100) * 10000;
        $itemsSaleDiscount = fake()->boolean(70) ? fake()->numberBetween(1, 10) * 5000 : 0;
        $cartDiscount = fake()->boolean(30) ? fake()->numberBetween(1, 10) * 5000 : 0;

        $discountTotal = $itemsSaleDiscount + $cartDiscount;
        $subtotal = $itemsSubtotal - $itemsSaleDiscount;

        $shippingTotal = fake()->randomElement([0, 35000, 45000, 60000]);
        $grandTotal = $subtotal - $cartDiscount + $shippingTotal;

        return [
            'order_number' => $orderNumber,
            'user_id' => User::factory(),

            'status' => fake()->randomElement(OrderStatus::cases()),
            'payment_status' => fake()->randomElement(OrderPaymentStatus::cases()),

            'items_subtotal' => $itemsSubtotal,
            'items_sale_discount' => $itemsSaleDiscount,
            'cart_discount' => $cartDiscount,
            'discount_total' => $discountTotal,
            'shipping_total' => $shippingTotal,
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,

            'tracking_number' => fake()->boolean() ? fake()->numerify('TRK-##########') : null,
            'customer_notes' => fake()->boolean(30) ? fake()->sentence() : null,

            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}

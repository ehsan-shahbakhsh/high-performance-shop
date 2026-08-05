<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalUnitPrice = fake()->numberBetween(50, 500) * 10000;

        $hasDiscount = fake()->boolean(30);
        $promotionDiscount = $hasDiscount ? fake()->numberBetween(1, 10) * 10000 : 0;

        $saleUnitPrice = $originalUnitPrice - $promotionDiscount;
        $finalUnitPrice = $saleUnitPrice;

        $quantity = fake()->numberBetween(1, 10);
        $lineTotal = $finalUnitPrice * $quantity;

        $productName = 'محصول تستی ' . fake()->words(2, true);
        $variantName = fake()->randomElement(['رنگ مشکی / سایز L', 'رنگ سفید / ۲۵۶ گیگ', 'مدل استاندارد / گارانتی ۱۸ ماهه']);
        $sku = 'SKU-' . strtoupper(Str::random(8));

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),

            'sku' => $sku,
            'product_name' => $productName,
            'variant_name' => $variantName,

            'selected_options' => null,

            'snapshot' => null,

            'quantity' => $quantity,

            'original_unit_price' => $originalUnitPrice,
            'sale_unit_price' => $saleUnitPrice,
            'promotion_discount' => $promotionDiscount,
            'final_unit_price' => $finalUnitPrice,

            'line_total' => $lineTotal,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'warehouse_id' => Warehouse::factory(),

            'quantity' => fake()->numberBetween(0, 100),
            'reserved_quantity' => 0,
            'shelf_location' => fake()->bothify('Row-##-Shelf-??'),
            'low_stock_threshold' => 10,
        ];
    }

    public function configure(): Factory|InventoryFactory
    {
        return $this->afterCreating(function (Inventory $inventory) {
            $inventory->productVariant->update([
                'stock_quantity' => $inventory->productVariant->stock_quantity + $inventory->quantity
            ]);
        });
    }
}

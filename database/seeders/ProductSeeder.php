<?php

namespace Database\Seeders;

use App\Enums\ProductRelationType;
use App\Enums\ProductType;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tehran = Warehouse::factory()->create(['name' => 'تهران']);

        Product::factory()
            ->variable()
            ->count(5)
            ->create()
            ->each(function ($product) use ($tehran) {
                ProductVariant::factory()
                    ->count(3)
                    ->for($product)
                    ->has(
                        Inventory::factory()
                            ->count(1)
                            ->state(['warehouse_id' => $tehran->id])
                    )
                    ->create();
            });

        $mainProduct = Product::factory()->create(['name' => 'گوشی موبایل خفن']);
        $accessories = Product::factory()->count(3)->create(['type' => ProductType::Simple]);

        foreach ($accessories as $accessory) {
            ProductRelation::factory()->create([
                'product_id' => $mainProduct->id,
                'related_product_id' => $accessory->id,
                'type' => ProductRelationType::CrossSell,
            ]);
        }
    }
}

<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\{Attribute, AttributeOption, ProductVariant, VariantAttributeValue};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariantAttributeValue>
 */
class VariantAttributeValueFactory extends Factory
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
            'product_id' => function (array $attributes) {
                return ProductVariant::query()->find($attributes['product_variant_id'])->product_id;
            },

            'attribute_option_id' => AttributeOption::factory()
                ->for(Attribute::factory()->state(['type' => AttributeType::Select])),
            'attribute_id' => function (array $attributes) {
                return AttributeOption::query()->find($attributes['attribute_option_id'])->attribute_id;
            },
        ];
    }
}

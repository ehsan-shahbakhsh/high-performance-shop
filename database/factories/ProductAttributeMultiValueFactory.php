<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\{Attribute, AttributeOption, Product, ProductAttributeMultiValue};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttributeMultiValue>
 */
class ProductAttributeMultiValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attribute = Attribute::factory()->create(['type' => AttributeType::MultiSelect]);

        return [
            'product_id' => Product::factory(),
            'attribute_id' => $attribute->id,
            'attribute_option_id' => AttributeOption::factory()->state(['attribute_id' => $attribute->id]),
        ];
    }

    public function withOption(?AttributeOption $option = null): static
    {
        return $this->state(function (array $attributes) use ($option) {
            if (!$option) {
                $option = AttributeOption::factory()->create([
                    'attribute_id' => $attributes['attribute_id'],
                ]);
            }

            return [
                'attribute_id' => $option->attribute_id,
                'attribute_option_id' => $option->id,
            ];
        });
    }
}

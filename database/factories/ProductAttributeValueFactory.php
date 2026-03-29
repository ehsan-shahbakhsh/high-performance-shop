<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\{Attribute, AttributeOption, Product, ProductAttributeValue};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttributeValue>
 */
class ProductAttributeValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attribute = Attribute::factory()->create(['type' => fake()->randomElement([
            AttributeType::Text,
            AttributeType::Textarea,
            AttributeType::Number,
            AttributeType::Boolean,
            AttributeType::Date,
            AttributeType::Select,
        ])]);

        $attributeOptionId = null;
        $valueText = null;
        $valueNumber = null;
        $valueBoolean = null;
        $valueDate = null;

        switch ($attribute->type) {
            case AttributeType::Text:
            case AttributeType::Textarea:
                $valueText = fake()->word();
                break;

            case AttributeType::Number:
                $valueNumber = fake()->randomFloat(2, 1, 999);
                break;

            case AttributeType::Boolean:
                $valueBoolean = fake()->boolean();
                break;

            case AttributeType::Date:
                $valueDate = fake()->date();
                break;

            case AttributeType::Select:
                $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);
                $attributeOptionId = $option->id;
                break;

        }

        return [
            'product_id' => Product::factory(),
            'attribute_id' => $attribute->id,

            'attribute_option_id' => $attributeOptionId,
            'value_text' => $valueText,
            'value_number' => $valueNumber,
            'value_boolean' => $valueBoolean,
            'value_date' => $valueDate,
        ];
    }
}

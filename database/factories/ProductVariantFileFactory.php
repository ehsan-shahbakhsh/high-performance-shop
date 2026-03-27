<?php

namespace Database\Factories;

use App\Models\{ProductVariant, ProductVariantFile};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariantFile>
 */
class ProductVariantFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $variant = ProductVariant::factory()->create();

        $variant
            ->addMediaFromString('fake content')
            ->usingFileName('file.zip')
            ->toMediaCollection('variant_file', 'local');

        return [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,

            'display_name' => fake()->words(3, true),

            'version' => $this->randomVersion(),

            'download_limit' => fake()->optional()->numberBetween(1, 10),

            'expiry_days' => fake()->optional()->numberBetween(7, 365),

            'position' => fake()->numberBetween(0, 100),

            'is_active' => fake()->boolean(90),
        ];
    }

    public function withVariant(?ProductVariant $variant = null): static
    {
        return $this->state(function () use ($variant) {
            $variant ??= ProductVariant::factory()->create();

            return [
                'product_variant_id' => $variant->id,
                'product_id' => $variant->product_id,
            ];
        });
    }

    private function randomVersion(): string
    {
        $major = fake()->numberBetween(0, 10);
        $minor = fake()->numberBetween(0, 10);
        $patch = fake()->numberBetween(0, 10);

        return sprintf("%s.%s.%s", $major, $minor, $patch);
    }
}

<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemDownload;
use App\Models\ProductVariantFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItemDownload>
 */
class OrderItemDownloadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'user_id' => User::factory(),
            'variant_file_id' => ProductVariantFile::factory(),

            'display_name' => 'فایل ضمیمه ' . fake()->word() . '.zip',

            'download_limit' => fake()->boolean() ? fake()->numberBetween(3, 10) : null,
            'download_count' => 0,
            'last_downloaded_at' => null,

            'expires_at' => fake()->boolean() ? now()->addMonths(fake()->numberBetween(1, 12)) : null,
            'revoked_at' => null,

            'token' => Str::random(64),
        ];
    }
}

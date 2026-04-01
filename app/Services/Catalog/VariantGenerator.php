<?php

namespace App\Services\Catalog;

use App\Models\{Product, ProductVariant};
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class VariantGenerator
{
    /**
     * Create a new class instance.
     */
    public function __construct(private SkuGenerator $skuGenerator)
    {
    }

    /**
     * @param Product $product
     * @param array<int, array{
     *     attribute_id: int,
     *     attribute_option_ids: int[],
     * }> $attributes
     * @param int $price
     * @return int
     * @throws Throwable
     */
    public function generate(Product $product, array $attributes, int $price): int
    {
        return DB::transaction(function () use ($product, $attributes, $price) {
            $count = 0;

            foreach ($this->cartesian($attributes) as $key => $combination) {
                $signature = ProductVariant::makeSignature(array_values($combination));

                $variant = ProductVariant::query()
                    ->firstOrCreate([
                        'product_id' => $product->id,
                        'signature' => $signature,
                    ], [
                        'product_id' => $product->id,
                        'price' => $price,
                        'is_default' => $key === 0,
                        'position' => $key + 1,
                        'signature' => $signature,
                    ]);

                if (!$variant->wasRecentlyCreated) continue;

                $valuesData = [];
                foreach ($combination as $attr => $optionId) {
                    $valuesData[] = [
                        'product_id' => $product->id,
                        'attribute_id' => str_replace('attr_', '', $attr),
                        'attribute_option_id' => $optionId,
                    ];
                }

                $variant->attributeValues()->createMany($valuesData);

                $variant->update([
                    'sku' => $this->skuGenerator->generate($product, $variant->getAttributeValuesForSku()),
                ]);

                $count++;
            }

            return $count;
        });
    }

    private function cartesian(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $values) {
            $tmp = [];

            $property = 'attr_' . $values['attribute_id'];

            foreach ($result as $resultItem) {
                foreach ($values['attribute_option_ids'] as $value) {
                    $tmp[] = array_merge($resultItem, [$property => $value]);
                }
            }

            $result = $tmp;
        }

        return $result;
    }
}

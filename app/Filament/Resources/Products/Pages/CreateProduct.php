<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductType;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Catalog\SkuGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @throws Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $variantColumns = [
                'price', 'sale_price', 'sku', 'sale_start', 'sale_end',
                'weight', 'length', 'width', 'height', 'is_default'
            ];

            $product = parent::handleRecordCreation(Arr::except($data, $variantColumns + ['attributes']));

            if ($product instanceof Product) $this->createAttributes($product, $data['attributes']);

            if ($product->type === ProductType::Simple) {
                if ($product instanceof Product && is_null($data['sku']))
                    $data['sku'] = resolve(SkuGenerator::class)
                        ->generate($product, attributes: $product->getAttributeValuesForSku());

                $data['is_default'] = true;
                $product->variants()->create(Arr::only($data, $variantColumns));
            }

            return $product;
        });
    }

    private function createAttributes(Product $product, array $attributes): void
    {
        $attributesData = [];
        $valuesData = [];
        $multiData = [];

        foreach ($attributes as $i => $attr) {
            $attributesData[] = [
                'position' => $i + 1,
                'attribute_id' => $attr['attribute_id'],
            ];

            $attributeId = $attr['attribute_id'];
            $optionIds = $attr['attribute_option_ids'] ?? null;

            if (!empty($optionIds)) {
                foreach ($optionIds as $optionId) {
                    $multiData[] = [
                        'attribute_id' => $attributeId,
                        'attribute_option_id' => $optionId,
                    ];
                }
                continue;
            }

            $valuesData[] = [
                'attribute_id' => $attributeId,
                'attribute_option_id' => $attr['attribute_option_id'] ?? null,
                'value_string' => $attr['value_string'] ?? null,
                'value_text' => $attr['value_text'] ?? null,
                'value_number' => $attr['value_number'] ?? null,
                'value_boolean' => $attr['value_boolean'] ?? null,
                'value_date' => $attr['value_date'] ?? null,
            ];
        }

        $product->attributes()->createMany($attributesData);
        $product->attributeValues()->createMany($valuesData);
        $product->attributeMultiValues()->createMany($multiData);
    }
}

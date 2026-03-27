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

            $product = parent::handleRecordCreation(Arr::except($data, $variantColumns));

            if ($product->type === ProductType::Simple) {
                if ($product instanceof Product && is_null($data['sku']))
                    $data['sku'] = resolve(SkuGenerator::class)->generate($product, attributes: []); // todo: set attributes

                $data['is_default'] = true;
                $product->variants()->create(Arr::only($data, $variantColumns));
            }

            return $product;
        });
    }
}

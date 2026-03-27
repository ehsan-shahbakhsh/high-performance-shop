<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductType;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Catalog\SkuGenerator;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $product = $this->getRecord();

        if ($product->type === ProductType::Simple) {
            $defaultVariant = $product->defaultVariant;

            $data['price'] = $defaultVariant->price;
            $data['sale_price'] = $defaultVariant->sale_price;
            $data['sku'] = $defaultVariant->sku;
            $data['sale_start'] = $defaultVariant->sale_start;
            $data['sale_end'] = $defaultVariant->sale_end;
            $data['weight'] = $defaultVariant->weight;
            $data['length'] = $defaultVariant->length;
            $data['width'] = $defaultVariant->width;
            $data['height'] = $defaultVariant->height;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $variantColumns = [
            'price', 'sale_price', 'sku', 'sale_start', 'sale_end', 'weight', 'length', 'width', 'height'
        ];

        $record->update(Arr::except($data, $variantColumns));

        if ($data['type'] === ProductType::Simple) {
            $defaultVariant = $record->defaultVariant;

            if ($record instanceof Product && is_null($data['sku']))
                $data['sku'] = resolve(SkuGenerator::class)->generate($record, attributes: [], ignore: $defaultVariant->id); // todo: set attributes

            $defaultVariant->update(Arr::only($data, $variantColumns));

            $this->form->fill($data);
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

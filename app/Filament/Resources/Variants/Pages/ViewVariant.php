<?php

namespace App\Filament\Resources\Variants\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Variants\VariantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVariant extends ViewRecord
{
    protected static string $resource = VariantResource::class;

    public function getBreadcrumbs(): array
    {
        $record = $this->getRecord();

        return [
            ProductResource::getIndexUrl() => ProductResource::getBreadcrumb(),

            ProductResource::getUrl('view', ['record' => $record->product_id]) => $record->product->name,

            VariantResource::getUrl('view', ['record' => $record->id]) => $this->getRecordTitle(),

            $this->getBreadcrumb(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Variants\Pages;

use App\Exceptions\BusinessException;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Variants\VariantResource;
use App\Services\Catalog\SkuGenerator;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVariant extends EditRecord
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            $data['sku'] ??= resolve(SkuGenerator::class)
                ->generate($record->product, attributes: [], ignore: $record->id); // todo: set attributes


            $this->form->fill($data);

            return parent::handleRecordUpdate($record, $data);
        } catch (BusinessException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
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

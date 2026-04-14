<?php

namespace App\Filament\Resources\ShippingCarriers\Pages;

use App\Filament\Resources\ShippingCarriers\ShippingCarrierResource;
use App\Models\ShippingCarrier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditShippingCarrier extends EditRecord
{
    protected static string $resource = ShippingCarrierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->handleWith(/**
                 * @throws Throwable
                 */ static function (ShippingCarrier $record): ?bool {
                    $record->methods()->delete();
                    return $record->deleteOrFail();
                }),
            ForceDeleteAction::make(),
            RestoreAction::make()
                ->handleWith(static function (ShippingCarrier $record): ?bool {
                    if (!method_exists($record, 'restore')) {
                        return false;
                    }

                    $record->methods()->restore();
                    return $record->restoreOrFail();
                }),
        ];
    }
}

<?php

namespace App\Filament\Resources\AttributeSets\Pages;

use App\Filament\Resources\AttributeSets\AttributeSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAttributeSet extends EditRecord
{
    protected static string $resource = AttributeSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

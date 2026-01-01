<?php

namespace App\Filament\Resources\AttributeSets\Pages;

use App\Filament\Resources\AttributeSets\AttributeSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttributeSets extends ListRecords
{
    protected static string $resource = AttributeSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Attributes\Pages;

use App\Enums\AttributeType;
use App\Filament\Resources\Attributes\AttributeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['is_variant']) {
            $data['type'] = AttributeType::Select;
        }

        return $data;
    }
}

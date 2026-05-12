<?php

namespace App\Filament\Resources\Discounts\Pages;

use App\Enums\DiscountType;
use App\Filament\Resources\Discounts\DiscountResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        if ($data['type'] == DiscountType::BuyXGetY->value) {
            $data['amount'] = 0;
        }

        return parent::handleRecordCreation($data);
    }
}

<?php

namespace App\Filament\Resources\ProductCategories\Pages;

use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListProductCategories extends ListRecords
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tree_view')
                ->label('نمای درختی')
                ->icon(Heroicon::OutlinedListBullet)
                ->color('gray')
                ->url(static::getResourceUrl('tree')),

            CreateAction::make(),
        ];
    }
}

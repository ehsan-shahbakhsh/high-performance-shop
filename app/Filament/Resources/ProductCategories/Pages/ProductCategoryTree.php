<?php

namespace App\Filament\Resources\ProductCategories\Pages;

use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentTree\Resources\Pages\TreePage;

class ProductCategoryTree extends TreePage
{
    protected static string $resource = ProductCategoryResource::class;

    protected static int $maxDepth = 5;

    protected static ?string $title = 'مدیریت ساختار دسته‌ها';

    protected ?string $heading = 'مدیریت ساختار دسته‌ها';

    protected function getTreeToolbarActions(): array
    {
        return [];
    }

    protected function getWithRelationQuery(): Builder
    {
        $depth = static::$maxDepth;
        $eagerLoad = implode('.', array_fill(0, $depth, 'children'));

        return $this->getTreeQuery()->with($eagerLoad);
    }

    protected function getActions(): array
    {
        return [
            $this->getCreateAction(),
            // SAMPLE CODE, CAN DELETE
            //\Filament\Pages\Actions\Action::make('sampleAction'),
        ];
    }

    protected function hasDeleteAction(): bool
    {
        return false;
    }

    protected function hasEditAction(): bool
    {
        return true;
    }

    protected function hasViewAction(): bool
    {
        return true;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    // CUSTOMIZE ICON OF EACH RECORD, CAN DELETE
    public function getTreeRecordIcon(?\Illuminate\Database\Eloquent\Model $record = null): ?string
    {
        return null;
    }
}

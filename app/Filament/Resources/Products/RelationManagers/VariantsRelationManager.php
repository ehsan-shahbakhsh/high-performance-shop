<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\ProductType;
use App\Filament\Resources\Variants\VariantResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class VariantsRelationManager extends RelationManager
{
    protected static ?string $relatedResource = VariantResource::class;

    protected static string $relationship = 'variants';

    protected static string|null|\BackedEnum $icon = Heroicon::OutlinedSwatch;

    protected static ?string $title = 'تنوع محصول (واریانت‌ها)';

    protected static ?string $modelLabel = 'تنوع';

    protected static ?string $pluralLabel = 'تنوع‌ها';

    protected function canDelete(Model $record): bool
    {
        $product = $this->getOwnerRecord();

        if ($product->type === ProductType::Simple) {
            return false;
        }

        return $product->variants()->count() > 1;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type !== ProductType::Simple;
    }
}

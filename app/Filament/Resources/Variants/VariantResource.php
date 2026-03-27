<?php

namespace App\Filament\Resources\Variants;

use App\Filament\Resources\Variants\Pages\{EditVariant, ViewVariant};
use App\Filament\Resources\Variants\Schemas\{VariantForm, VariantInfolist};
use App\Filament\Resources\Variants\Tables\VariantsTable;
use App\Models\ProductVariant;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\{Builder, SoftDeletingScope};

class VariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $recordTitleAttribute = 'sku';

    protected static ?string $title = 'تنوع محصول (واریانت‌ها)';

    protected static ?string $modelLabel = 'تنوع';

    protected static ?string $pluralLabel = 'تنوع‌ها';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return VariantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VariantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VariantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewVariant::route('/{record}'),
            'edit' => EditVariant::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

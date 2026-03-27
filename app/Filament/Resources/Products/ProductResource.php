<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\{CreateProduct, EditProduct, ListProducts, ViewProduct};
use App\Filament\Resources\Products\RelationManagers\{RelatedProductsRelationManager, VariantsRelationManager};
use App\Filament\Resources\Products\Schemas\{ProductForm, ProductInfolist};
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Resources\Variants\VariantResource;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\{Builder, SoftDeletingScope};
use BackedEnum;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'محصول';

    protected static ?string $pluralModelLabel = 'محصولات';

    protected static ?string $navigationLabel = 'محصولات';

    protected static ?int $navigationSort = 3;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['media', 'brand'])
            ->withSum('variants', 'stock_quantity');
    }

    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [
            static::getRouteBaseName() . '.*',
            VariantResource::getRouteBaseName() . '.*',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
            RelatedProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
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

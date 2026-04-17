<?php

namespace App\Filament\Resources\Discounts;

use App\Filament\Resources\Discounts\Pages\{CreateDiscount, EditDiscount, ListDiscounts, ViewDiscount};
use App\Filament\Resources\Discounts\RelationManagers\CouponsRelationManager;
use App\Filament\Resources\Discounts\Schemas\{DiscountForm, DiscountInfolist};
use App\Filament\Resources\Discounts\Tables\DiscountsTable;
use App\Models\Discount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'تخفیف';

    protected static ?string $pluralModelLabel = 'جشنواره‌ها و تخفیف‌ها';

    protected static ?string $navigationLabel = 'جشنواره‌ها و تخفیف‌ها';

    protected static string|null|\UnitEnum $navigationGroup = 'مارکتینگ و فروش';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'coupons' => CouponsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/create'),
            'view' => ViewDiscount::route('/{record}'),
            'edit' => EditDiscount::route('/{record}/edit'),
        ];
    }
}

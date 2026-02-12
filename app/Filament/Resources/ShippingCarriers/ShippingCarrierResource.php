<?php

namespace App\Filament\Resources\ShippingCarriers;

use App\Filament\Resources\ShippingCarriers\Pages\CreateShippingCarrier;
use App\Filament\Resources\ShippingCarriers\Pages\EditShippingCarrier;
use App\Filament\Resources\ShippingCarriers\Pages\ListShippingCarriers;
use App\Filament\Resources\ShippingCarriers\Schemas\ShippingCarrierForm;
use App\Filament\Resources\ShippingCarriers\Tables\ShippingCarriersTable;
use App\Models\ShippingCarrier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShippingCarrierResource extends Resource
{
    protected static ?string $model = ShippingCarrier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'شرکت حمل‌ونقل';
    protected static ?string $pluralModelLabel = 'شرکت‌های حمل‌ونقل';
    protected static ?string $navigationLabel = 'شرکت‌های حمل‌ونقل';

    protected static string|null|\UnitEnum $navigationGroup = 'مدیریت ارسال';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ShippingCarrierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingCarriersTable::configure($table);
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
            'index' => ListShippingCarriers::route('/'),
            'create' => CreateShippingCarrier::route('/create'),
            'edit' => EditShippingCarrier::route('/{record}/edit'),
        ];
    }
}

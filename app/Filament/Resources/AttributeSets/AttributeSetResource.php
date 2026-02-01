<?php

namespace App\Filament\Resources\AttributeSets;

use App\Filament\Resources\AttributeSets\Pages\CreateAttributeSet;
use App\Filament\Resources\AttributeSets\Pages\EditAttributeSet;
use App\Filament\Resources\AttributeSets\Pages\ListAttributeSets;
use App\Filament\Resources\AttributeSets\RelationManagers\GroupsRelationManager;
use App\Filament\Resources\AttributeSets\Schemas\AttributeSetForm;
use App\Filament\Resources\AttributeSets\Tables\AttributeSetsTable;
use App\Models\AttributeSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttributeSetResource extends Resource
{
    protected static ?string $model = AttributeSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'قالب ویژگی';

    protected static ?string $pluralModelLabel = 'قالب‌های ویژگی';

    /**
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return 'قالب‌های ویژگی';
    }

    public static function form(Schema $schema): Schema
    {
        return AttributeSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttributeSetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            GroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributeSets::route('/'),
            'create' => CreateAttributeSet::route('/create'),
            'edit' => EditAttributeSet::route('/{record}/edit'),
        ];
    }
}

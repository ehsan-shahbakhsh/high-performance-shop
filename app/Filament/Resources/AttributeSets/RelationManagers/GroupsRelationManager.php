<?php

namespace App\Filament\Resources\AttributeSets\RelationManagers;

use App\Filament\Resources\AttributeSets\AttributeSetResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    protected static ?string $modelLabel = 'گروه ویژگی';
    protected static ?string $pluralLabel = 'گروه‌های ویژگی';

    protected static ?string $title = 'گروه‌های ویژگی';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('نام گروه')
                    ->required()
                    ->placeholder('مثال: صفحه نمایش')
                    ->maxLength(255),

                TextInput::make('position')
                    ->numeric()
                    ->label('موقعیت')
                    ->default(0),

                Select::make('attributes')
                    ->label('ویژگی‌های این گروه')
                    ->relationship('attributes', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->pivotData(['position' => 0]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('position')
            ->modifyQueryUsing(fn($query) => $query->withCount('attributes'))
            ->columns([
                TextColumn::make('name')
                    ->label('نام گروه')
                    ->toggleable(),
                TextColumn::make('attributes_count')
                    ->counts('attributes')
                    ->label('تعداد ویژگی‌ها')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

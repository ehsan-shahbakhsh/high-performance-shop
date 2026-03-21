<?php

namespace App\Filament\Resources\AttributeGroups\RelationManagers;

use App\Filament\Resources\Attributes\AttributeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Table;

class AttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributes';

    protected static ?string $relatedResource = AttributeResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->searchable()
                    ->label('نام')
                    ->toggleable(),

                TextColumn::make('type')
                    ->badge()
                    ->label('نوع')
                    ->toggleable(),

                IconColumn::make('is_filterable')
                    ->boolean()
                    ->label('قابل فیلتر')
                    ->toggleable(),

                IconColumn::make('is_required')
                    ->boolean()
                    ->label('اجباری')
                    ->toggleable(),

                IconColumn::make('is_variant')
                    ->boolean()
                    ->label('ویژگی تنوع محصول')
                    ->toggleable(),

                TextColumn::make('options.label')
                    ->label('نمونه مقادیر')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList()
                    ->expandableLimitedList()
                    ->placeholder('بدون مقدار')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ ایجاد')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ آخرین بروزرسانی')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}

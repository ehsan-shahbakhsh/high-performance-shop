<?php

namespace App\Filament\Resources\Provinces\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProvincesTable
{
    public static function configure(Table $table): Table
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
                    ->label('نام استان')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name_en')
                    ->searchable()
                    ->label('نام انگلیسی')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tel_prefix')
                    ->sortable()
                    ->label('پیش‌شماره')
                    ->toggleable(),

                TextColumn::make('cities_count')
                    ->counts('cities')
                    ->label('تعداد شهرها')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextInputColumn::make('position')
                    ->sortable()
                    ->label('ترتیب')
                    ->rules(['required', 'int', 'min:0'])
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('وضعیت')
                    ->onColor('success')
                    ->offColor('danger')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ آخرین بروزرسانی')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}

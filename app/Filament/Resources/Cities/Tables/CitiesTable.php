<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('نام شهر')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('province.name')
                    ->searchable()
                    ->label('نام استان')
                    ->toggleable(),

                TextColumn::make('name_en')
                    ->searchable()
                    ->label('نام انگلیسی')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('has_shipping')
                    ->label('حمل‌ونقل')
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
                SelectFilter::make('province_id')
                    ->relationship('province', 'name')
                    ->label('استان')
                    ->searchable()
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('has_shipping')
                    ->label('وضعیت حمل‌ونقل')
                    ->placeholder('همه')
                    ->trueLabel('دارد')
                    ->falseLabel('ندارد'),
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}

<?php

namespace App\Filament\Resources\ShippingMethods\Tables;

use App\Filament\Components\ShopTable;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ShippingMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ShopTable::id(),

                TextColumn::make('carrier.name')
                    ->label('شرکت حمل‌ونقل')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('code')
                    ->label('کد')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('min_delivery_time')
                    ->label('حداقل زمان')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('max_delivery_time')
                    ->label('حداکثر زمان')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_cod_supported')
                    ->label('پرداخت در محل')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),

                TextColumn::make('max_weight')
                    ->label('سقف وزن (گرم)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                ShopTable::position(),

                ShopTable::status(),

                ShopTable::createdAt(),
                ShopTable::updatedAt(),
                ShopTable::deletedAt(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت')
                    ->placeholder('همه موارد')
                    ->trueLabel('فقط فعال‌ها')
                    ->falseLabel('فقط غیرفعال‌ها'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),

                ActionGroup::make([
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}

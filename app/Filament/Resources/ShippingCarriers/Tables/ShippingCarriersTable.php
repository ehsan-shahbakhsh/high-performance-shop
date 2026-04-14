<?php

namespace App\Filament\Resources\ShippingCarriers\Tables;

use App\Filament\Components\ShopTable;
use App\Models\ShippingCarrier;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Throwable;

class ShippingCarriersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ShopTable::id(),

                ImageColumn::make('logo_path')
                    ->label('لوگو')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('code')
                    ->label('کد')
                    ->badge()
                    ->color('gray')
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
                    DeleteAction::make()
                        ->handleWith(/**
                         * @throws Throwable
                         */ static function (ShippingCarrier $record): ?bool {
                            $record->methods()->delete();
                            return $record->deleteOrFail();
                        }),

                    ForceDeleteAction::make(),

                    RestoreAction::make()
                        ->handleWith(static function (ShippingCarrier $record): ?bool {
                            if (!method_exists($record, 'restore')) {
                                return false;
                            }

                            $record->methods()->restore();
                            return $record->restoreOrFail();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}

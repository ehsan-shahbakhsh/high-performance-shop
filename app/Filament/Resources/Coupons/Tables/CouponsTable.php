<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Filament\Components\ShopTable;
use App\Filament\Resources\Discounts\RelationManagers\CouponsRelationManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ShopTable::id(),

                TextColumn::make('discount.name')
                    ->label('نام جشنواره')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->hiddenOn(CouponsRelationManager::class),

                TextColumn::make('code')
                    ->label('کد تخفیف')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('کد تخفیف کپی شد')
                    ->toggleable(),

                TextColumn::make('usage_limit')
                    ->label('سقف کل استفاده')
                    ->numeric()
                    ->sortable()
                    ->placeholder('بی‌نهایت')
                    ->toggleable(),

                TextColumn::make('user_usage_limit')
                    ->label('سقف استفاده کاربر')
                    ->numeric()
                    ->sortable()
                    ->placeholder('نامحدود')
                    ->toggleable(),

                TextColumn::make('used')
                    ->label('دفعات استفاده')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                ShopTable::status(),

                TextColumn::make('expires_at')
                    ->label('تاریخ انقضا')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->placeholder('بدون انقضا')
                    ->toggleable(),

                ShopTable::createdAt(),
                ShopTable::updatedAt(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

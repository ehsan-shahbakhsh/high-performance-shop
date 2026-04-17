<?php

namespace App\Filament\Resources\Discounts\Tables;

use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Filament\Components\ShopTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ShopTable::id(),

                TextColumn::make('name')
                    ->label('عنوان جشنواره')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('value')
                    ->label('مقدار تخفیف')
                    ->formatStateUsing(static fn($record) => $record->type === DiscountType::Percentage
                        ? "{$record->amount}%"
                        : number_format($record->amount) . ' تومان'
                    )
                    ->badge()
                    ->color('success'),

                TextColumn::make('scope')
                    ->label('قلمرو')
                    ->badge(),

                TextColumn::make('usage_count')
                    ->label('استفاده شده')
                    ->formatStateUsing(static fn($record) => "{$record->used} / " . ($record->usage_limit ?? '∞'))
                    ->description('تعداد کل دفعات استفاده'),

                TextColumn::make('starts_at')
                    ->label('تاریخ شروع')
                    ->dateTime('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ends_at')
                    ->label('تاریخ پایان')
                    ->dateTime('Y/m/d')
                    ->sortable()
                    ->color(static fn($state) => now()->gt($state) ? 'danger' : 'gray'),

                ShopTable::status(),

                ShopTable::createdAt(),
                ShopTable::updatedAt(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('فقط فعال‌ها'),

                SelectFilter::make('scope')
                    ->label('فیلتر قلمرو')
                    ->options(DiscountScope::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

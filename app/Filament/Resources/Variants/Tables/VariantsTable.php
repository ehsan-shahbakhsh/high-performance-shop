<?php

namespace App\Filament\Resources\Variants\Tables;

use App\Exceptions\BusinessException;
use App\Filament\Components\ShopTable;
use App\Models\ProductVariant;
use App\Services\Catalog\SkuGenerator;
use Filament\Actions\{
    BulkActionGroup,
    CreateAction,
    DeleteBulkAction,
    EditAction,
    ForceDeleteBulkAction,
    RestoreBulkAction,
    ViewAction,
};
use Filament\Notifications\Notification;
use Filament\Tables\Columns\{TextColumn, ToggleColumn};
use Filament\Tables\Filters\{Filter, TernaryFilter, TrashedFilter};
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ShopTable::id(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                ShopTable::price(),
                ShopTable::price('sale_price', 'قیمت فروش ویژه')
                    ->color('danger')
                    ->placeholder('ندارد'),

                TextColumn::make('stock_quantity')
                    ->label('موجودی')
                    ->sortable()
                    ->color(static fn($state) => match (true) {
                        intval($state) == 0 => 'danger',
                        intval($state) < 5 => 'warning',
                        default => 'success'
                    })
                    ->badge()
                    ->toggleable(),

                ToggleColumn::make('is_default')
                    ->label('پیشفرض')
                    ->updateStateUsing(static function (Model $record, $state) {
                        try {
                            $record->update(['is_default' => $state]);
                        } catch (BusinessException $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->toggleable(),

                ShopTable::status(),

                ShopTable::position(),

                ShopTable::createdAt(),
                ShopTable::updatedAt(),
                ShopTable::deletedAt(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت')
                    ->placeholder('همه')
                    ->trueLabel('فقط فعال‌ها')
                    ->falseLabel('فقط غیرفعال‌ها'),

                Filter::make('out_of_stock')
                    ->label('ناموجودها')
                    ->query(static fn($query) => $query->where('stock_quantity', '<=', 0)),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(static function (ProductVariant $record, $livewire) {
                        if (!$record->sku) {
                            $product = $livewire->getOwnerRecord();

                            $record->update([
                                'sku' => resolve(SkuGenerator::class)
                                    ->generate($product, attributes: $record->getAttributeValuesForSku()),
                            ]);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

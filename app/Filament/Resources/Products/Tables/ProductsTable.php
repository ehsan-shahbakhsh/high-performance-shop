<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductOutOfStockAction;
use App\Enums\ProductType;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->label('تصویر شاخص')
                    ->collection('gallery')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->label('نام')
                    ->toggleable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('attributeSet.name')
                    ->label('مجموعه ویژگی‌ها')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('type')
                    ->badge()
                    ->sortable()
                    ->label('نوع')
                    ->toggleable(),

                TextColumn::make('price')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                    ->label('قیمت')
                    ->toggleable()
                    ->description(function (Product $record) {
                        $lines = [];

                        if ($record->sale_price) {
                            $lines[] = '<span class="text-danger-600 font-bold">🔥 حراج: ' . number_format($record->sale_price) . ' تومان' . '</span>';
                        }

                        if ($record->out_of_stock_action === ProductOutOfStockAction::Text) {
                            $lines[] = '<span class="text-primary-600">✉️ متن: ' . e($record->custom_stock_text) . '</span>';
                        } elseif ($record->out_of_stock_action === ProductOutOfStockAction::Hidden) {
                            $lines[] = '<span class="text-gray-500 text-xs">👁️‍🗨️ قیمت در سایت مخفی است</span>';
                        }

                        if (empty($lines)) {
                            return null;
                        }

                        return new HtmlString(implode('<br>', $lines));
                    }),

                IconColumn::make('manage_stock')
                    ->boolean()
                    ->label('مدیریت موجودی')
                    ->toggleable(),

                TextColumn::make('stock_sum')
                    ->label('موجودی کل')
                    ->state(function ($record) {
                        if ($record->type === ProductType::Simple && $record->manage_stock) {
                            return $record->variants_sum_stock_quantity;
                        }
                        if ($record->type === ProductType::Variable) {
                            return $record->variants_sum_stock_quantity;
                        }
                        return '∞';
                    })
                    ->badge()
                    ->color(fn($state) => $state === 0 ? 'danger' : 'success')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('وضعیت انتشار')
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
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ حذف')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('out_of_stock_action')
                    ->label('فیلتر رفتار ناموجودی')
                    ->options(ProductOutOfStockAction::class)
                    ->multiple(),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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

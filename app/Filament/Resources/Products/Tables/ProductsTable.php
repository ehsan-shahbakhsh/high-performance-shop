<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\{ProductOutOfStockAction, ProductStatus, ProductType};
use Filament\Actions\{
    BulkActionGroup,
    DeleteBulkAction,
    EditAction,
    ForceDeleteBulkAction,
    RestoreBulkAction,
    ViewAction,
};
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\{IconColumn, SpatieMediaLibraryImageColumn, TextColumn};
use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
use Filament\Tables\Table;

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

                SpatieMediaLibraryImageColumn::make('thumbnail_url')
                    ->label('تصویر شاخص')
                    ->collection('product_gallery')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->label('نام')
                    ->toggleable(),

                TextColumn::make('type')
                    ->badge()
                    ->sortable()
                    ->label('نوع')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->label('وضعیت')
                    ->toggleable(),

                TextColumn::make('min_price')
                    ->label('شروع قیمت')
                    ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('manage_stock')
                    ->boolean()
                    ->label('مدیریت موجودی')
                    ->toggleable(),

                TextColumn::make('brand.name')
                    ->label('برند')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('stock_sum')
                    ->label('موجودی کل')
                    ->state(function ($record) {
                        if ($record->type === ProductType::Simple && $record->manage_stock) {
                            return $record->variants_sum_stock_quantity ?? 0;
                        }
                        if ($record->type === ProductType::Variable) {
                            return $record->variants_sum_stock_quantity ?? 0;
                        }
                        return '∞';
                    })
                    ->badge()
                    ->color(fn($state) => intval($state) === 0 ? 'danger' : 'success')
                    ->toggleable(),

                IconColumn::make('is_virtual')
                    ->boolean()
                    ->label('محصول مجازی')
                    ->toggleable(),

                IconColumn::make('is_downloadable')
                    ->boolean()
                    ->label('قابل دانلود')
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

//                TernaryFilter::make('in_stock')
//                    ->label('وضعیت موجودی')
//                    ->placeholder('همه محصولات')
//                    ->trueLabel('فقط موجودها')
//                    ->falseLabel('ناموجودها')
//                    ->queries(
//                        true: fn (Builder $query) => $query->where('quantity', '>', 0),
//                        false: fn (Builder $query) => $query->where('quantity', '<=', 0),
//                    ),

                SelectFilter::make('brand')
                    ->label('برند')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('type')
                    ->label('نوع محصول')
                    ->options(ProductType::class)
                    ->multiple(),

                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(ProductStatus::class)
                    ->multiple(),

//                SelectFilter::make('category')
//                    ->label('دسته‌بندی')
//                    ->relationship('category', 'name')
//                    ->searchable()
//                    ->multiple(),

//                TernaryFilter::make('has_discount')
//                    ->label('وضعیت تخفیف')
//                    ->queries(
//                        true: fn (Builder $query) => $query->whereHas('discounts', fn($q) => $q->active()),
//                        false: fn (Builder $query) => $query->whereDoesntHave('discounts'),
//                    ),

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

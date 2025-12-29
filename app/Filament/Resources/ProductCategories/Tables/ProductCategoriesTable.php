<?php

namespace App\Filament\Resources\ProductCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('path')
            ->paginated(false)
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('cover')
                    ->label('تصویر')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('نام دسته‌بندی')
                    ->formatStateUsing(function ($state, Model $record) {
                        return str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $record->level) . '— ' . $state;
                    })
                    ->html()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('parent.name')
                    ->label('والد')
                    ->badge()
                    ->color('gray')
                    ->placeholder('ریشه اصلی')
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('فعال')
                    ->toggleable(),

                IconColumn::make('is_featured')
                    ->label('ویژه')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('include_in_menu')
                    ->label('نمایش در منو')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('products_count')
                    ->label('تعداد محصول')
//                    ->counts('products')
                    ->badge()
                    ->toggleable(),

                IconColumn::make('icon')
                    ->label('آیکون')
                    ->icon(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ ایجاد')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ بروزرسانی')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('فیلتر بر اساس والد')
                    ->relationship('parent', 'name'),
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

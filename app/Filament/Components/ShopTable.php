<?php

namespace App\Filament\Components;

use Filament\Tables\Columns\{TextColumn, TextInputColumn, ToggleColumn};

final class ShopTable
{
    public static function id(): TextColumn
    {
        return TextColumn::make('id')
            ->sortable()
            ->label('شناسه')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function position(string $name = 'position', string $label = 'ترتیب نمایش'): TextInputColumn
    {
        return TextInputColumn::make($name)
            ->label($label)
            ->inputMode('number')
            ->rules(['required', 'int', 'min:0'])
            ->step(1)
            ->sortable()
            ->extraAttributes(['style' => 'max-width:80px'])
            ->toggleable();
    }

    public static function status(string $name = 'is_active', string $label = 'وضعیت'): ToggleColumn
    {
        return ToggleColumn::make($name)
            ->label($label)
            ->onColor('success')
            ->offColor('danger')
            ->toggleable();
    }

    public static function price(string $name = 'price', string $label = 'قیمت'): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(static fn($state) => number_format($state) . ' تومان')
            ->sortable()
            ->toggleable();
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->label('تاریخ ایجاد')
            ->formatStateUsing(static fn($state) => verta($state)->formatDatetime())
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function updatedAt(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->label('تاریخ آخرین بروزرسانی')
            ->formatStateUsing(static fn($state) => verta($state)->formatDatetime())
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function deletedAt(): TextColumn
    {
        return TextColumn::make('deleted_at')
            ->dateTime()
            ->sortable()
            ->label('تاریخ حذف')
            ->formatStateUsing(static fn($state) => verta($state)->formatDatetime())
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
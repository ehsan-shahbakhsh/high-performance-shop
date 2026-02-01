<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Enums\TagType;
use App\Models\Tag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->label('نام')
                    ->description(fn(Tag $record) => $record->slug)
                    ->icon(fn(Tag $record) => $record->icon)
                    ->color(fn(Tag $record) => $record->color ? Color::hex($record->color) : null)
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('نوع')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('usage_count')
                    ->label('محصولات')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                ToggleColumn::make('is_featured')
                    ->label('ویژه')
                    ->onColor('warning')
                    ->offColor('gray')
                    ->toggleable(),

                ToggleColumn::make('is_visible')
                    ->label('نمایش')
                    ->onColor('success')
                    ->offColor('danger')
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
                    ->label('تاریخ بروزرسانی')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع برچسب')
                    ->options(TagType::class),

                TernaryFilter::make('is_visible')
                    ->label('وضعیت انتشار')
                    ->placeholder('همه')
                    ->trueLabel('فقط منتشر شده‌ها (فعال)')
                    ->falseLabel('فقط پیش‌نویس‌ها (مخفی)')
                    ->indicator('وضعیت انتشار'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedTag)
            ->emptyStateHeading('هنوز برچسبی ندارید')
            ->emptyStateDescription('برچسب‌ها به مشتریان کمک می‌کنند محصولات را راحت‌تر پیدا کنند.');
    }
}

<?php

namespace App\Filament\Resources\Attributes\Tables;

use App\Enums\AttributeType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AttributesTable
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

                TextColumn::make('name')
                    ->searchable()
                    ->label('نام')
                    ->toggleable(),

                TextColumn::make('type')
                    ->badge()
                    ->label('نوع')
                    ->toggleable(),

                IconColumn::make('is_filterable')
                    ->boolean()
                    ->label('قابل فیلتر')
                    ->toggleable(),

                IconColumn::make('is_required')
                    ->boolean()
                    ->label('اجباری')
                    ->toggleable(),

                TextColumn::make('options.label')
                    ->label('نمونه مقادیر')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList()
                    ->expandableLimitedList()
                    ->placeholder('بدون مقدار')
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
                    ->label('نوع')
                    ->options(AttributeType::class),
                TernaryFilter::make('is_filterable')
                    ->label('قابلیت فیلتر')
                    ->placeholder('همه موارد')
                    ->trueLabel('فقط قابل فیلتر')
                    ->falseLabel('غیر قابل فیلتر'),
                TernaryFilter::make('is_required')
                    ->label('وضعیت الزام')
                    ->placeholder('همه موارد')
                    ->trueLabel('فقط اجباری‌ها')
                    ->falseLabel('فقط اختیاری‌ها'),
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

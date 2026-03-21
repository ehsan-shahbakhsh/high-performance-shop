<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Enums\AttributeType;
use Filament\Infolists\Components\{RepeatableEntry, IconEntry, TextEntry};
use Filament\Schemas\Components\{Group, Section, Grid};
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class AttributeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('اطلاعات پایه')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('group.name')
                                    ->label('گروه')
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('بدون گروه'),

                                TextEntry::make('name')
                                    ->label('نام ویژگی')
                                    ->weight('bold')
                                    ->size(TextSize::Large),

                                TextEntry::make('type')
                                    ->label('نوع نمایش')
                                    ->badge(),

                                IconEntry::make('is_filterable')
                                    ->label('قابلیت فیلتر')
                                    ->boolean(),

                                IconEntry::make('is_required')
                                    ->label('اجباری')
                                    ->boolean(),

                                IconEntry::make('is_variant')
                                    ->label('ویژگی تنوع محصول')
                                    ->boolean(),
                            ]),

                        Section::make('مقادیر تعریف شده')
                            ->schema([
                                RepeatableEntry::make('options')
                                    ->label('لیست گزینه‌ها')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('label')
                                            ->label('عنوان')
                                            ->weight('medium'),

                                        TextEntry::make('value')
                                            ->label('مقدار سیستمی')
                                            ->color('gray')
                                            ->size(TextSize::Small),
                                    ]),
                            ])
                            ->visible(fn($record) => in_array($record->type, [AttributeType::Select, AttributeType::MultiSelect])),

                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                Section::make('زمان‌بندی')
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('تاریخ ایجاد')
                                            ->dateTime('Y/m/d H:i')
                                            ->since(),
                                    ])->collapsible(),
                            ]),
                    ]),
            ]);
    }
}

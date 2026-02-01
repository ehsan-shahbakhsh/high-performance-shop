<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Enums\AttributeType;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
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

                                        ColorEntry::make('color_view')
                                            ->label('رنگ')
                                            ->state(fn($record) => $record->value)
                                            ->visible(fn($record) => $schema->getRecord()->type === AttributeType::Color),

                                        TextEntry::make('system_value')
                                            ->label('مقدار سیستمی')
                                            ->state(fn($record) => $record->value)
                                            ->color('gray')
                                            ->size(TextSize::Small)
                                            ->visible(fn($record) => $schema->getRecord()->type !== AttributeType::Color),
                                    ]),
                            ])
                            ->visible(fn($record) => in_array($record->type, [AttributeType::Select, AttributeType::MultiSelect, AttributeType::Color])),

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

<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Enums\AttributeType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تعریف ویژگی')
                    ->description('مشخصات اصلی و نوع داده‌ای ویژگی را تعیین کنید.')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        TextInput::make('name')
                            ->label('نام نمایشی')
                            ->placeholder('مثال: رنگ، سایز، حافظه داخلی')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                                if (($get('code') ?? '') !== Str::slug($old) && filled($get('code'))) {
                                    return;
                                }
                                $set('code', Str::slug($state));
                            }),

                        TextInput::make('code')
                            ->label('کد سیستمی (Slug)')
                            ->placeholder('مثال: color, internal_storage')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->hintIcon('heroicon-m-question-mark-circle')
                            ->hintIconTooltip('یک شناسه یکتا برای استفاده در سیستم (انگلیسی)'),

                        Select::make('type')
                            ->label('نوع داده')
                            ->options(AttributeType::class)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('options', [])),

                    ])->columns(2),

                Section::make('تنظیمات و رفتار')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_filterable')
                                    ->label('قابل فیلتر کردن در فروشگاه')
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip('آیا کاربر می‌تواند در سایدبار فروشگاه بر اساس این ویژگی فیلتر کند؟')
                                    ->default(false)
                                    ->onIcon('heroicon-s-funnel')
                                    ->offIcon('heroicon-o-x-mark')
                                    ->onColor('success'),

                                Toggle::make('is_required')
                                    ->label('اجباری')
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip('آیا پر کردن این ویژگی برای محصول الزامی است؟')
                                    ->default(false)
                                    ->onIcon('heroicon-s-shield-check')
                                    ->offIcon('heroicon-o-x-mark'),

                            ]),
                    ]),

                Section::make('مدیریت گزینه‌ها')
                    ->description('گزینه‌های قابل انتخاب برای این ویژگی را تعریف کنید (مثل: قرمز، آبی، سبز).')
                    ->icon('heroicon-o-list-bullet')
                    ->collapsible()
                    ->schema([
                        Repeater::make('options')
                            ->label('گزینه ها')
                            ->relationship()
                            ->schema([
                                TextInput::make('label')
                                    ->label('عنوان گزینه')
                                    ->placeholder('مثال: قرمز')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('value', $state)),

                                ColorPicker::make('color_ui')
                                    ->label('کد رنگ')
                                    ->required()
                                    ->visible(fn(Get $get) => $get('../../type') === AttributeType::Color)
                                    ->dehydrated(false)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('value', $state))
                                    ->afterStateHydrated(function (Set $set, Get $get) {
                                        $set('color_ui', $get('value'));
                                    }),

                                TextInput::make('text_ui')
                                    ->label('مقدار ذخیره شده')
                                    ->required()
                                    ->visible(fn(Get $get) => in_array($get('../../type'), [AttributeType::Select, AttributeType::MultiSelect]))
                                    ->default(fn(Get $get) => $get('label'))
                                    ->maxLength(255)
                                    ->dehydrated(false)
                                    ->live(onBlur: true)
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip('مقداری که در دیتابیس یا فیلترها استفاده می‌شود')
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('value', $state))
                                    ->afterStateHydrated(function (Set $set, Get $get) {
                                        $set('text_ui', $get('value'));
                                    }),

                                Hidden::make('value')
                                    ->required(),
                            ])
                            ->orderColumn('position')
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('افزودن گزینه جدید')
                            ->reorderableWithButtons()
                            ->cloneable(),
                    ])
                    ->visible(fn(Get $get) => in_array($get('type'), [
                        AttributeType::Select,
                        AttributeType::MultiSelect,
                        AttributeType::Color ,
                    ])),
            ]);
    }
}

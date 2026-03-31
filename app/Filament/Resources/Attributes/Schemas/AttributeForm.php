<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Enums\AttributeType;
use Filament\Actions\Action;
use Filament\Forms\Components\{TextInput, Toggle, Select, Repeater};
use Filament\Schemas\Components\{Grid, Section};
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use function Pest\Laravel\instance;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تعریف ویژگی')
                    ->description('مشخصات اصلی و نوع داده‌ای ویژگی را تعیین کنید.')
                    ->icon(Heroicon::OutlinedCube)
                    ->schema([
                        Select::make('attribute_group_id')
                            ->label('گروه ویژگی')
                            ->placeholder('یک گروه انتخاب کنید')
                            ->relationship('group', 'name')
                            ->preload()
                            ->searchable()
                            ->hintIcon(Heroicon::QuestionMarkCircle)
                            ->hintIconTooltip('گروه‌بندی برای نمایش در صفحه محصول و مرتب‌سازی در پنل مدیریت')
                            ->createOptionForm([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('نام گروه')
                                            ->required()
                                            ->maxLength(200)
                                            ->unique(ignoreRecord: true),

                                        TextInput::make('position')
                                            ->label('ترتیب نمایش')
                                            ->numeric()
                                            ->default(0),
                                    ]),
                            ])
                            ->createOptionAction(static fn(Action $action) => $action->label('ایجاد گروه جدید')),

                        TextInput::make('name')
                            ->label('نام نمایشی')
                            ->placeholder('مثال: رنگ، سایز، حافظه داخلی')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(static function (Get $get, Set $set, ?string $state, ?string $old) {
                                if (!filled($get('code')) || $get('code') === Str::slug($old)) {
                                    $set('code', Str::slug($state));
                                }
                            }),

                        TextInput::make('code')
                            ->label('کد سیستمی (Slug)')
                            ->placeholder('مثال: color, internal_storage')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->hintIcon(Heroicon::QuestionMarkCircle)
                            ->hintIconTooltip('شناسه یکتای این ویژگی در سطح سیستم و برای استفاده داخلی (فقط حروف انگلیسی و خط تیره)'),

                        Select::make('type')
                            ->label('نوع داده')
                            ->options(AttributeType::class)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(static function ($state, $old, Set $set) {
                                $optionBasedTypes = [AttributeType::Select, AttributeType::MultiSelect];

                                if (in_array($state, $optionBasedTypes, true) && in_array($old, $optionBasedTypes, true)) {
                                    return;
                                }

                                $set('options', []);
                            })
                            ->visible(static fn(Get $get) => $get('is_variant') === false),

                    ])->columns(2),

                Section::make('تنظیمات و رفتار')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_filterable')
                                    ->label('قابل فیلتر کردن در فروشگاه')
                                    ->hintIcon(Heroicon::QuestionMarkCircle)
                                    ->hintIconTooltip('آیا کاربر می‌تواند در سایدبار فروشگاه بر اساس این ویژگی فیلتر کند؟')
                                    ->default(false)
                                    ->onIcon(Heroicon::Funnel)
                                    ->offIcon(Heroicon::OutlinedXMark)
                                    ->onColor('success'),

                                Toggle::make('is_required')
                                    ->label('اجباری')
                                    ->hintIcon(Heroicon::QuestionMarkCircle)
                                    ->hintIconTooltip('آیا پر کردن این ویژگی برای محصول الزامی است؟')
                                    ->default(false)
                                    ->onIcon(Heroicon::ShieldCheck)
                                    ->offIcon(Heroicon::OutlinedXMark),

                                Toggle::make('is_variant')
                                    ->label('استفاده برای تنوع محصول')
                                    ->hintIcon(Heroicon::QuestionMarkCircle)
                                    ->hintIconTooltip('اگر فعال باشد، محصول با گزینه‌های این ویژگی نوع‌های مختلف خواهد داشت (مثلاً رنگ یا حافظه)')
                                    ->default(false)
                                    ->onIcon(Heroicon::Squares2x2)
                                    ->offIcon(Heroicon::OutlinedXMark)
                                    ->onColor('info')
                                    ->live()
                                    ->afterStateUpdated(static function ($state, Set $set) {
                                        if ($state) {
                                            $set('type', AttributeType::Select);
                                        } else {
                                            $set('type', null);
                                            $set('options', []);
                                        }
                                    }),
                            ]),
                    ]),

                Section::make('مدیریت گزینه‌ها')
                    ->description('گزینه‌های قابل انتخاب برای این ویژگی را تعریف کنید (مثل: قرمز، آبی، سبز).')
                    ->icon(Heroicon::OutlinedListBullet)
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
                                    ->afterStateUpdated(static function (Set $set, Get $get, ?string $state) {
                                        if (blank($get('value'))) {
                                            $set('value', $state);
                                        }
                                    }),

                                TextInput::make('value')
                                    ->label('مقدار ذخیره شده')
                                    ->required()
                                    ->default(static fn(Get $get) => $get('label'))
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->hintIcon(Heroicon::QuestionMarkCircle)
                                    ->hintIconTooltip('مقداری که در دیتابیس یا فیلترها استفاده می‌شود'),
                            ])
                            ->orderColumn('position')
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('افزودن گزینه جدید')
                            ->reorderableWithButtons()
                            ->cloneable(),
                    ])
                    ->visible(static fn(Get $get) => in_array($get('type'), [
                        AttributeType::Select,
                        AttributeType::MultiSelect,
                    ])),
            ]);
    }
}

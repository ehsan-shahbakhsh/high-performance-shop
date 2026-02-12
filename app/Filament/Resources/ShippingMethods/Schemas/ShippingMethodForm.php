<?php

namespace App\Filament\Resources\ShippingMethods\Schemas;

use App\Filament\Components\ShopForm;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ShippingMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('مشخصات روش')
                            ->columns(2)
                            ->schema([
                                Select::make('carrier_id')
                                    ->relationship('carrier', 'name')
                                    ->label('شرکت حمل‌ونقل')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('name')
                                    ->label('نام روش (نمایش به کاربر)')
                                    ->required()
                                    ->placeholder('مثلاً: پست پیشتاز'),

                                TextInput::make('code')
                                    ->label('کد یکتا')
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Textarea::make('description')
                                    ->label('توضیحات')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('محدودیت‌ها')
                            ->columns(2)
                            ->schema([
                                TextInput::make('max_weight')
                                    ->label('حداکثر وزن (گرم)')
                                    ->numeric()
                                    ->suffix('گرم'),

                                Toggle::make('is_cod_supported')
                                    ->label('پشتیبانی از پرداخت در محل')
                                    ->inline(false),
                            ]),

                        Section::make('تنظیمات درایور (فنی)')
                            ->schema([
                                KeyValue::make('settings')
                                    ->label('پارامترهای اتصال')
                                    ->keyLabel('کلید (مثلاً ServiceID)')
                                    ->valueLabel('مقدار')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ])
                            ->collapsed()
                            ->description('تنظیمات اختصاصی مربوط به درایور انتخاب شده (JSON)'),
                    ])->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('زمان تحویل (پیش‌فرض)')
                            ->schema([
                                TextInput::make('min_virtual')
                                    ->label('حداقل زمان')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->suffix(fn (Get $get) => match ((int)$get('unit_virtual')) {
                                        1440 => 'روز',
                                        60 => 'ساعت',
                                        default => 'دقیقه',
                                    })
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, Get $get, ?Model $record) {
                                        if (!$record || $record->min_delivery_time === null) return;

                                        $min = $record->min_delivery_time;
                                        $max = $record->max_delivery_time ?? 0;

                                        $divider = 1;
                                        if ($min % 1440 === 0 && $max % 1440 === 0) $divider = 1440;
                                        elseif ($min % 60 === 0 && $max % 60 === 0) $divider = 60;

                                        $component->state($min / $divider);
                                    }),

                                TextInput::make('max_virtual')
                                    ->label('حداکثر زمان')
                                    ->numeric()
                                    ->required()
                                    ->live()
                                    ->suffix(fn (Get $get) => match ((int)$get('unit_virtual')) {
                                        1440 => 'روز',
                                        60 => 'ساعت',
                                        default => 'دقیقه',
                                    })
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, ?Model $record) {
                                        if (!$record || $record->max_delivery_time === null) return;

                                        $min = $record->min_delivery_time ?? 0;
                                        $max = $record->max_delivery_time;

                                        $divider = 1;
                                        if ($min % 1440 === 0 && $max % 1440 === 0) $divider = 1440;
                                        elseif ($min % 60 === 0 && $max % 60 === 0) $divider = 60;

                                        $component->state($max / $divider);
                                    }),

                                Select::make('unit_virtual')
                                    ->label('واحد زمانی')
                                    ->options([
                                        1 => 'دقیقه',
                                        60 => 'ساعت',
                                        1440 => 'روز',
                                    ])
                                    ->default(1)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, ?Model $record) {
                                        if (!$record) return;

                                        $min = $record->min_delivery_time ?? 0;
                                        $max = $record->max_delivery_time ?? 0;

                                        if ($min > 0 && $max > 0) {
                                            if ($min % 1440 === 0 && $max % 1440 === 0) {
                                                $component->state(1440);
                                            } elseif ($min % 60 === 0 && $max % 60 === 0) {
                                                $component->state(60);
                                            } else {
                                                $component->state(1);
                                            }
                                        }
                                    }),

                                Hidden::make('min_delivery_time')
                                    ->dehydrateStateUsing(fn (Get $get) => (int)$get('min_virtual') * (int)$get('unit_virtual')),

                                Hidden::make('max_delivery_time')
                                    ->dehydrateStateUsing(fn (Get $get) => (int)$get('max_virtual') * (int)$get('unit_virtual')),
                            ]),

                        Section::make('وضعیت')
                            ->schema([
                                ShopForm::status('is_active', 'فعال است'),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }
}

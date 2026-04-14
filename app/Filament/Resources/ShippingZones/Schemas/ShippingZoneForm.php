<?php

namespace App\Filament\Resources\ShippingZones\Schemas;

use App\Enums\ShippingZoneLocationType;
use App\Filament\Components\ShopForm;
use App\Models\City;
use App\Models\Province;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ShippingZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('اطلاعات منطقه')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('نام منطقه')
                                    ->required()
                                    ->placeholder('مثلاً: تهران و حومه')
                                    ->columnSpan(1),

                                TextInput::make('code')
                                    ->label('کد یکتا')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('مثلاً: tehran_zone')
                                    ->columnSpan(1),

                                Textarea::make('description')
                                    ->label('توضیحات داخلی')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('مناطق تحت پوشش')
                            ->description('استان‌ها و شهرهایی که شامل این منطقه می‌شوند را اضافه کنید.')
                            ->schema([
                                Repeater::make('locations')
                                    ->relationship()
                                    ->label('مکان')
                                    ->itemLabel(static function (array $state): ?string {
                                        $provinceName = Province::query()->find($state['province_id'] ?? null)?->name;
                                        $cityName = City::query()->find($state['city_id'] ?? null)?->name;

                                        if (!$provinceName && !$cityName) return 'مکان جدید';

                                        return collect([$provinceName, $cityName])->filter()->implode(' - ');
                                    })
                                    ->schema([
                                        Grid::make()
                                            ->columns(3)
                                            ->schema([
                                                Select::make('province_id')
                                                    ->label('استان')
                                                    ->options(Province::all()->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->nullable()
                                                    ->placeholder('همه استان‌ها (کل کشور)')
                                                    ->helperText('خالی بگذارید تا شامل کل کشور شود.')
                                                    ->live()
                                                    ->afterStateUpdated(static fn(Set $set) => $set('city_id', null)),

                                                Select::make('city_id')
                                                    ->label('شهر')
                                                    ->placeholder('همه شهرها')
                                                    ->options(static function (Get $get) {
                                                        $provinceId = $get('province_id');
                                                        if (!$provinceId) {
                                                            return [];
                                                        }
                                                        return City::query()->where('province_id', $provinceId)->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->nullable()
                                                    ->distinct()
                                                    ->helperText('خالی بگذارید تا شامل کل استان شود.'),

                                                ToggleButtons::make('type')
                                                    ->label('وضعیت پوشش')
                                                    ->options(ShippingZoneLocationType::class)
                                                    ->inline()
                                                    ->default(ShippingZoneLocationType::Include)
                                                    ->required(),
                                            ]),
                                    ])
                                    ->addActionLabel('افزودن مکان جدید')
                                    ->collapsible()
                                    ->collapseAllAction(static fn($action) => $action->label('بستن همه'))
                                    ->defaultItems(1),
                            ]),
                    ])
                    ->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('تنظیمات')
                            ->schema([
                                ShopForm::status('is_active', 'فعال است'),
                            ]),
                    ])
                    ->columnSpan(1),
            ])->columns(3);
    }
}

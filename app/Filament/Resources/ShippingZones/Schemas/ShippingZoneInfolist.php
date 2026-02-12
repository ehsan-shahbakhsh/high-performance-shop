<?php

namespace App\Filament\Resources\ShippingZones\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShippingZoneInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $schema->getRecord()->load(['locations.province', 'locations.city']);

        return $schema
            ->components([
                Section::make('مشخصات منطقه')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('نام منطقه')
                            ->weight('bold'),

                        TextEntry::make('code')
                            ->label('کد سیستمی')
                            ->fontFamily('mono')
                            ->copyable()
                            ->badge()
                            ->color('gray'),

                        IconEntry::make('is_active')
                            ->label('وضعیت')
                            ->boolean(),

                        TextEntry::make('position')
                            ->label('اولویت')
                            ->icon('heroicon-m-arrow-up-circle'),

                        TextEntry::make('description')
                            ->label('توضیحات')
                            ->columnSpanFull()
                            ->placeholder('بدون توضیحات'),
                    ]),

                Section::make('مکان‌های تعریف شده')
                    ->description('لیست استان‌ها و شهرهایی که شامل این منطقه می‌شوند.')
                    ->schema([
                        RepeatableEntry::make('locations')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('province.name')
                                            ->label('استان')
                                            ->icon('heroicon-m-map')
                                            ->placeholder('همه استان‌ها'),

                                        TextEntry::make('city.name')
                                            ->label('شهر')
                                            ->placeholder('همه شهرها (کل استان)'),

                                        TextEntry::make('type')
                                            ->label('نوع پوشش')
                                            ->badge(),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('تاریخچه')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاریخ ایجاد')
                            ->dateTime()
                            ->formatStateUsing(fn($state) => verta($state)->format('j F Y - H:i'))
                            ->color('gray'),

                        TextEntry::make('updated_at')
                            ->label('آخرین بروزرسانی')
                            ->dateTime()
                            ->formatStateUsing(fn($state) => verta($state)->format('j F Y - H:i'))
                            ->color('gray'),
                    ]),
            ]);
    }
}

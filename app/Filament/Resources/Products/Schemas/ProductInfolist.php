<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                ->schema([
                    Group::make()
                        ->schema([
                            Section::make('اطلاعات محصول')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            ImageEntry::make('thumbnail')
                                                ->hiddenLabel()
                                                ->height(150)
                                                ->extraImgAttributes(['class' => 'rounded-lg shadow']),

                                            Group::make([
                                                TextEntry::make('name')
                                                    ->label('نام محصول')
                                                    ->size(TextSize::Large)
                                                    ->weight('bold'),

                                                TextEntry::make('sku')
                                                    ->label('SKU')
                                                    ->fontFamily('mono')
                                                    ->copyable()
                                                    ->badge()
                                                    ->color('gray'),
                                            ]),
                                        ]),
                                ]),

                            Tabs::make('Details')
                                ->tabs([
                                    Tab::make('توضیحات')
                                        ->icon('heroicon-m-document-text')
                                        ->schema([
                                            TextEntry::make('description')
                                                ->markdown()
                                                ->prose()
                                                ->hiddenLabel(),
                                        ]),

                                    Tab::make('مشخصات فنی')
                                        ->icon('heroicon-m-wrench')
                                        ->schema([
                                            TextEntry::make('slug')
                                                ->url(fn ($record) => url("/product/{$record->slug}"), true)
                                                ->color('info'),

                                            TextEntry::make('attributeSet.name')
                                                ->label('دسته ویژگی'),
                                        ]),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    Group::make()
                        ->schema([
                            Section::make('وضعیت')
                                ->schema([
                                    IconEntry::make('is_active')
                                        ->label('وضعیت انتشار')
                                        ->boolean(),

                                    IconEntry::make('manage_stock')
                                        ->label('مدیریت انبار')
                                        ->boolean(),
                                ]),

                            Section::make('قیمت‌گذاری')
                                ->schema([
                                    TextEntry::make('price')
                                        ->label('قیمت اصلی')
                                        ->money('IRT'),

                                    TextEntry::make('sale_price')
                                        ->label('فروش ویژه')
                                        ->money('IRT')
                                        ->color('danger')
                                        ->placeholder('-'),
                                ]),

                            Section::make('تاریخچه')
                                ->collapsed()
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
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Variants\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class VariantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تصاویر تنوع')
                    ->icon(Heroicon::Photo)
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('variant_gallery')
                            ->collection('variant_gallery')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('شناسنامه تنوع')
                    ->icon(Heroicon::FingerPrint)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('sku')
                            ->label('کد انبار (SKU)')
                            ->weight('bold')
                            ->fontFamily('mono')
                            ->copyable()
                            ->icon(Heroicon::QrCode),

                        IconEntry::make('is_default')
                            ->label('تنوع پیش‌فرض')
                            ->boolean()
                            ->trueIcon(Heroicon::Star)
                            ->falseIcon(Heroicon::Minus)
                            ->trueColor('warning')
                            ->falseColor('gray'),

                        IconEntry::make('is_active')
                            ->label('وضعیت انتشار')
                            ->boolean(),
                    ]),

                Section::make('وضعیت انبار و قیمت‌گذاری')
                    ->icon(Heroicon::CurrencyDollar)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('price')
                            ->label('قیمت اصلی')
                            ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                            ->size(TextSize::Large),

                        TextEntry::make('sale_price')
                            ->label('قیمت فروش ویژه')
                            ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                            ->color(fn($state) => $state ? 'success' : 'gray')
                            ->placeholder('بدون تخفیف'),

                        TextEntry::make('stock_quantity')
                            ->label('موجودی انبار')
                            ->numeric()
                            ->size(TextSize::Large)
                            ->badge()
                            ->color(fn($state) => match (true) {
                                $state <= 0 => 'danger',
                                $state < 10 => 'warning',
                                default => 'success',
                            })
                            ->icon(fn($state) => match (true) {
                                $state <= 0 => Heroicon::XCircle,
                                $state < 10 => Heroicon::ExclamationTriangle,
                                default => Heroicon::CheckCircle,
                            }),
                    ]),

                Section::make('زمان‌بندی تخفیف')
                    ->icon(Heroicon::Clock)
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('sale_start')
                            ->label('شروع تخفیف')
                            ->formatStateUsing(fn($state) => $state ? verta($state)->format('j F Y - H:i') : '—'),

                        TextEntry::make('sale_end')
                            ->label('پایان تخفیف')
                            ->formatStateUsing(fn($state) => $state ? verta($state)->format('j F Y - H:i') : '—'),
                    ]),

                Section::make('ابعاد و حمل و نقل')
                    ->icon(Heroicon::Cube)
                    ->columns(4)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('weight')
                            ->label('وزن')
                            ->suffix(' گرم')
                            ->placeholder('—'),

                        TextEntry::make('length')
                            ->label('طول')
                            ->suffix(' cm')
                            ->placeholder('—'),

                        TextEntry::make('width')
                            ->label('عرض')
                            ->suffix(' cm')
                            ->placeholder('—'),

                        TextEntry::make('height')
                            ->label('ارتفاع')
                            ->suffix(' cm')
                            ->placeholder('—'),
                    ]),

                Section::make('اطلاعات سیستمی')
                    ->icon(Heroicon::CpuChip)
                    ->columns(2)
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
            ]);
    }
}

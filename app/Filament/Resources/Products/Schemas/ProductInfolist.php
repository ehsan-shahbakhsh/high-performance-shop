<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Infolists\Components\{IconEntry, ImageEntry, RepeatableEntry, SpatieMediaLibraryImageEntry, TextEntry};
use Filament\Schemas\Components\{Fieldset, Grid, Group, Section, Tabs, Tabs\Tab};
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $defaultVariant = $schema->getRecord()->defaultVariant;

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
                                                ImageEntry::make('thumbnail_url')
                                                    ->hiddenLabel()
                                                    ->height(150)
                                                    ->width(150)
                                                    ->extraImgAttributes(['class' => 'rounded-lg shadow object-cover']),

                                                Group::make([
                                                    TextEntry::make('name')
                                                        ->label('نام محصول')
                                                        ->size(TextSize::Large)
                                                        ->weight('bold'),

                                                    TextEntry::make('brand.name')
                                                        ->label('برند')
                                                        ->placeholder('-')
                                                        ->badge(),

                                                    TextEntry::make('type')
                                                        ->label('نوع محصول')
                                                        ->badge(),
                                                ]),
                                            ]),

                                        TextEntry::make('short_description')
                                            ->label('توضیح کوتاه')
                                            ->placeholder('-'),
                                    ]),

                                Tabs::make('Details')
                                    ->tabs([
                                        Tab::make('توضیحات')
                                            ->icon(Heroicon::DocumentText)
                                            ->schema([
                                                TextEntry::make('description')
                                                    ->markdown()
                                                    ->prose()
                                                    ->hiddenLabel(),
                                            ]),

                                        Tab::make('مشخصات فنی')
                                            ->icon(Heroicon::Wrench)
                                            ->schema([
                                                Fieldset::make('ابعاد و وزن بسته‌بندی')
                                                    ->schema([
                                                        TextEntry::make('weight')
                                                            ->label('وزن')
                                                            ->getStateUsing($defaultVariant->weight ? number_format($defaultVariant->weight) . ' گرم' : '-')
                                                            ->badge()
                                                            ->color('warning'),

                                                        TextEntry::make('length')
                                                            ->label('طول')
                                                            ->getStateUsing($defaultVariant->length ? $defaultVariant->length . ' سانتی‌متر' : '-'),

                                                        TextEntry::make('width')
                                                            ->label('عرض')
                                                            ->getStateUsing($defaultVariant->width ? $defaultVariant->width . ' سانتی‌متر' : '-'),

                                                        TextEntry::make('height')
                                                            ->label('ارتفاع')
                                                            ->getStateUsing($defaultVariant->height ? $defaultVariant->height . ' سانتی‌متر' : '-'),
                                                    ])
                                                    ->columns(4),

                                                TextEntry::make('slug')
                                                    ->label('نامک (Slug)')
                                                    ->url(static fn($record) => url("/products/{$record->slug}"), true)
                                                    ->color('info'),

                                                TextEntry::make('out_of_stock_action')
                                                    ->label('عملیات هنگام اتمام موجودی')
                                                    ->badge(),

                                                TextEntry::make('custom_stock_text')
                                                    ->label('متن سفارشی اتمام موجودی')
                                                    ->placeholder('-'),
                                            ]),

                                        Tab::make('ویژگی‌ها')
                                            ->visible(static fn(Product $record) => $record->attributes()->exists())
                                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                                            ->schema([
                                                RepeatableEntry::make('display_attributes')
                                                    ->label('ویژگی‌های محصول')
                                                    ->schema([
                                                        TextEntry::make('attribute_name')
                                                            ->label('ویژگی'),

                                                        TextEntry::make('display_value')
                                                            ->label('مقدار'),
                                                    ])
                                                    ->columns(2),
                                            ]),

                                        Tab::make('گالری رسانه')
                                            ->icon(Heroicon::Photo)
                                            ->schema([
                                                Section::make('گالری تصاویر')
                                                    ->schema([
                                                        SpatieMediaLibraryImageEntry::make('gallery')
                                                            ->collection('product_gallery')
                                                            ->label('تصاویر محصول')
                                                            ->columnSpanFull(),
                                                    ]),

                                                Section::make('ویدیوها')
                                                    ->collapsed()
                                                    ->schema([
                                                        SpatieMediaLibraryImageEntry::make('videos')
                                                            ->collection('product_videos')
                                                            ->label('ویدیوهای محصول')
                                                            ->columnSpanFull(),
                                                    ]),
                                            ]),

                                        Tab::make('قیمت‌ها')
                                            ->icon(Heroicon::Banknotes)
                                            ->schema([
                                                TextEntry::make('min_price')
                                                    ->label('حداقل قیمت')
                                                    ->formatStateUsing(static fn($state) => number_format($state) . ' تومان')
                                                    ->placeholder('-'),

                                                TextEntry::make('max_price')
                                                    ->label('حداکثر قیمت')
                                                    ->formatStateUsing(static fn($state) => number_format($state) . ' تومان')
                                                    ->placeholder('-'),

                                                TextEntry::make('min_sale_price')
                                                    ->label('حداقل قیمت تخفیف')
                                                    ->formatStateUsing(static fn($state) => number_format($state) . ' تومان')
                                                    ->placeholder('-'),

                                                TextEntry::make('max_sale_price')
                                                    ->label('حداکثر قیمت تخفیف')
                                                    ->formatStateUsing(static fn($state) => number_format($state) . ' تومان')
                                                    ->placeholder('-'),
                                            ]),

                                        Tab::make('SEO')
                                            ->icon(Heroicon::GlobeAlt)
                                            ->schema([
                                                TextEntry::make('seo_title')
                                                    ->label('عنوان سئو')
                                                    ->placeholder('-'),

                                                TextEntry::make('seo_description')
                                                    ->label('توضیحات سئو')
                                                    ->placeholder('-'),
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
                                            ->boolean()
                                            ->tooltip(static fn($state) => $state
                                                ? 'این محصول در حال حاضر منتشر و برای کاربران قابل مشاهده است'
                                                : 'این محصول هنوز منتشر نشده و برای کاربران قابل مشاهده نیست'
                                            ),

                                        IconEntry::make('manage_stock')
                                            ->label('مدیریت انبار')
                                            ->boolean()
                                            ->tooltip(static fn($state) => $state
                                                ? 'موجودی این محصول به صورت خودکار مدیریت می‌شود'
                                                : 'موجودی این محصول مدیریت نمی‌شود و همیشه موجود فرض می‌شود'
                                            ),

                                        IconEntry::make('is_virtual')
                                            ->label('محصول مجازی')
                                            ->tooltip(static fn($state) => $state
                                                ? 'این یک محصول غیر فیزیکی است (ارسال ندارد)'
                                                : 'این محصول فیزیکی است و نیازمند ارسال است'
                                            ),

                                        IconEntry::make('is_downloadable')
                                            ->label('قابل دانلود')
                                            ->tooltip(static fn($state) => $state
                                                ? 'این محصول شامل فایل دانلودی است'
                                                : 'برای این محصول هیچ فایلی جهت دانلود وجود ندارد'
                                            ),

                                        TextEntry::make('status')
                                            ->label('وضعیت سیستم')
                                            ->badge(),
                                    ]),

                                Section::make('زمان انتشار')
                                    ->schema([
                                        TextEntry::make('published_at')
                                            ->hiddenLabel()
                                            ->dateTime()
                                            ->formatStateUsing(static fn($state) => verta($state)->format('j F Y - H:i'))
                                            ->placeholder('-')
                                            ->color('success'),
                                    ]),

                                Section::make('تاریخچه')
                                    ->collapsed()
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('تاریخ ایجاد')
                                            ->dateTime()
                                            ->formatStateUsing(static fn($state) => verta($state)->format('j F Y - H:i'))
                                            ->color('gray'),

                                        TextEntry::make('updated_at')
                                            ->label('آخرین بروزرسانی')
                                            ->dateTime()
                                            ->formatStateUsing(static fn($state) => verta($state)->format('j F Y - H:i'))
                                            ->color('gray'),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

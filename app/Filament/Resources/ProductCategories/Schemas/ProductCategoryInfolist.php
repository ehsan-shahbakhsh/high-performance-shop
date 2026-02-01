<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;

class ProductCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات کلی')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('نام دسته'),

                        TextEntry::make('parent.name')
                            ->label('دسته مادر')
                            ->placeholder('ریشه (بدون مادر)'),

                        TextEntry::make('slug')
                            ->label('نامک (Slug)')
                            ->fontFamily('mono'),

                        TextEntry::make('position')
                            ->label('ترتیب نمایش')
                            ->numeric(),
                    ]),

                Section::make('وضعیت و دسترسی')
                    ->columns(3)
                    ->compact()
                    ->schema([
                        IconEntry::make('is_active')
                            ->label('وضعیت فعال')
                            ->boolean(),

                        IconEntry::make('is_featured')
                            ->label('ویژه')
                            ->boolean(),

                        IconEntry::make('include_in_menu')
                            ->label('نمایش در منو')
                            ->boolean(),
                    ]),

                Section::make('مدیا')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('category_cover')
                            ->collection('category_cover')
                            ->label('تصویر کاور')
                            ->placeholder('بدون تصویر')
                            ->imageHeight(100),

                        IconEntry::make('icon')
                            ->label('آیکون')
                            ->icon(fn($state) => $state)
                            ->size(IconSize::Large),
                    ]),

                Section::make('تنظیمات سئو')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('seo_title')
                            ->label('عنوان سئو')
                            ->placeholder('-'),

                        TextEntry::make('seo_description')
                            ->label('توضیحات متا')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

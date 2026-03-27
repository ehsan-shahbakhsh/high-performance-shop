<?php

namespace App\Filament\Resources\Variants\Schemas;

use App\Filament\Components\ShopForm;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class VariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('جزئیات فنی و موجودی')
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->unique('product_variants', 'sku', ignoreRecord: true)
                            ->placeholder('مثلا: PROD-RED-XL')
                            ->maxLength(100)
                            ->rule('alpha_dash')
                            ->hintIcon(Heroicon::QuestionMarkCircle, 'در صورت خالی بودن، به صورت خودکار تولید می‌شود'),

                        Grid::make(1)
                            ->components([
                                ShopForm::status('is_active', 'وضعیت فعال بودن'),

                                Toggle::make('is_default')
                                    ->label('تنوع پیش‌فرض محصول')
                                    ->accepted(function ($livewire) use ($schema) {
                                        if (method_exists($livewire, 'getOwnerRecord')) {
                                            $product = $livewire->getOwnerRecord();
                                        } else {
                                            $product = $schema->getRecord()?->product;
                                        }

                                        return $product->variants()->count() === 0;
                                    })
                                    ->validationMessages([
                                        'accepted' => 'اولین تنوع محصول باید به عنوان پیش‌فرض ثبت شود.'
                                    ])
                            ]),
                    ])
                    ->columns(),

                Section::make('قیمت‌گذاری')
                    ->schema([
                        ShopForm::price(),

                        ShopForm::price('sale_price', 'قیمت فروش ویژه', false)
                            ->beforeOrEqual('price')
                            ->live(onBlur: true),
                    ])
                    ->columns(),

                Section::make('تصاویر تنوع')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->collection('variant_gallery')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->imageEditor()
                            ->panelLayout('grid')
                            ->maxFiles(10)
                            ->helperText('تصاویر مخصوص این تنوع محصول (مثلاً رنگ خاص) را آپلود کنید.'),
                    ])
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        DateTimePicker::make('sale_start')
                            ->label('شروع تخفیف')
                            ->seconds(false)
                            ->jalali()
                            ->hintIcon(Heroicon::QuestionMarkCircle, 'زمان شروع اعمال قیمت با تخفیف'),

                        DateTimePicker::make('sale_end')
                            ->label('پایان تخفیف')
                            ->seconds(false)
                            ->jalali()
                            ->hintIcon(Heroicon::QuestionMarkCircle, 'پس از این زمان، قیمت عادی محصول اعمال می‌شود'),
                    ])
                    ->visible(fn(Get $get) => filled($get('sale_price'))),

                Section::make('ابعاد و وزن')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('weight')
                                ->label('وزن (گرم)')
                                ->numeric()
                                ->integer()
                                ->suffix('g')
                                ->maxValue(50000)
                                ->hintIcon(Heroicon::QuestionMarkCircle, 'برای محاسبه هزینه ارسال استفاده می‌شود'),

                            TextInput::make('length')
                                ->label('طول')
                                ->numeric()
                                ->integer()
                                ->suffix('cm')
                                ->maxValue(300),

                            TextInput::make('width')
                                ->label('عرض')
                                ->numeric()
                                ->integer()
                                ->suffix('cm')
                                ->maxValue(300),

                            TextInput::make('height')
                                ->label('ارتفاع')
                                ->numeric()
                                ->integer()
                                ->suffix('cm')
                                ->maxValue(300),
                        ])
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

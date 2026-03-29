<?php

namespace App\Filament\Resources\Variants\Schemas;

use App\Enums\AttributeType;
use App\Filament\Components\ShopForm;
use App\Models\{Attribute, AttributeOption};
use Filament\Forms\Components\{
    DatePicker,
    DateTimePicker,
    Hidden,
    Repeater,
    Select,
    SpatieMediaLibraryFileUpload,
    Textarea,
    TextInput,
    Toggle,
};
use Filament\Schemas\Components\{Grid, Section};
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class VariantForm
{
    public static function configure(Schema $schema): Schema
    {
        $livewire = $schema->getLivewire();

        if (method_exists($livewire, 'getOwnerRecord')) {
            $product = $livewire->getOwnerRecord();
        } else {
            $product = $schema->getRecord()?->product;
        }

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
                                    ->accepted($product->variants()->count() === 0)
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

                Section::make('ویژگی‌ها')
                    ->schema([
                        Repeater::make('attributes')
                            ->relationship('attributeValues')
                            ->hiddenLabel()
                            ->addActionLabel('افزودن ویژگی')
                            ->schema([
                                Hidden::make('attribute_type')
                                    ->default(null),

                                Hidden::make('product_id')
                                    ->default($product->id),

                                Select::make('attribute_id')
                                    ->label('ویژگی')
                                    ->options(static function () {
                                        return Attribute::query()
                                            ->where('is_variant', true)
                                            ->whereNotIn('type', [AttributeType::MultiSelect, AttributeType::Textarea])
                                            ->pluck('name', 'id');
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(static function ($state, Set $set) {
                                        $type = Attribute::query()->find($state)?->type;

                                        $set('attribute_type', $type);
                                        $set('attribute_option_id', null);
                                    })
                                    ->afterStateHydrated(static function ($state, Set $set) {
                                        $type = Attribute::query()->find($state)?->type;

                                        $set('attribute_type', $type);
                                    })
                                    ->required()
                                    ->distinct()
                                    ->validationMessages([
                                        'distinct' => 'امکان ثبت ویژگی تکراری وجود ندارد.',
                                    ]),

                                Select::make('attribute_option_id')
                                    ->label('مقدار')
                                    ->options(static function (Get $get) {
                                        $attributeId = $get('attribute_id');
                                        if (!$attributeId) return [];

                                        return AttributeOption::query()
                                            ->where('attribute_id', $attributeId)
                                            ->pluck('label', 'id');
                                    })
                                    ->required()
                                    ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::Select),

                                Textarea::make('value_string')
                                    ->label('مقدار')
                                    ->rows(3)
                                    ->required()
                                    ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::Text),

                                Toggle::make('value_boolean')
                                    ->label('مقدار')
                                    ->inline(false)
                                    ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::Boolean),

                                TextInput::make('value_number')
                                    ->numeric()
                                    ->label('مقدار')
                                    ->required()
                                    ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::Number),

                                DatePicker::make('value_date')
                                    ->jalali()
                                    ->label('مقدار')
                                    ->required()
                                    ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::Date),
                            ])
                            ->columns(2)
                            ->default([]),
                    ])
                    ->columnSpanFull(),

                Section::make('تصاویر تنوع')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->hiddenLabel()
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
                    ->visible(static fn(Get $get) => filled($get('sale_price'))),

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

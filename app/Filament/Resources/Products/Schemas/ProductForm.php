<?php

namespace App\Filament\Resources\Products\Schemas;

use Illuminate\Validation\Rule;
use App\Enums\{ProductType, ProductStatus, AttributeType, ProductOutOfStockAction};
use App\Filament\Components\ShopForm;
use App\Models\{Attribute, AttributeOption, Brand, Product};
use Cviebrock\EloquentSluggable\Services\SlugService;
use Filament\Forms\Components\{DatePicker, DateTimePicker, Placeholder, RichEditor, Select};
use Filament\Forms\Components\{Hidden, Repeater, SpatieMediaLibraryFileUpload, TextInput, Textarea, Toggle};
use Filament\Schemas\Components\{Grid, Group, Section, Tabs, Tabs\Tab};
use Filament\Schemas\Components\Utilities\{Set, Get};
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('اطلاعات پایه')
                            ->schema([
                                TextInput::make('name')
                                    ->label('نام محصول')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                                        $oldSlug = SlugService::createSlug(Product::class, 'slug', $old ?? '');
                                        if (!filled($get('slug')) || $get('slug') === $oldSlug) {
                                            $set('slug', SlugService::createSlug(Product::class, 'slug', $state ?? ''));
                                        }
                                    }),

                                ShopForm::slug(Product::class, generateFrom: 'name'),

                                Placeholder::make('url_preview')
                                    ->label('آدرس محصول')
                                    ->extraAttributes(['dir' => 'ltr'])
                                    ->content(fn(Get $get) => filled($get('slug'))
                                        ? url('/products/' . $get('slug'))
                                        : '—'
                                    ),

                                RichEditor::make('description')
                                    ->label('توضیحات کامل')
                                    ->columnSpanFull(),

                                Textarea::make('short_description')
                                    ->label('توضیحات کوتاه (خلاصه)')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('قیمت‌گذاری، موجودی و ابعاد')
                            ->schema([
                                Grid::make(3)->schema([
                                    ShopForm::price(),

                                    ShopForm::price('sale_price', 'قیمت فروش ویژه', false)
                                        ->beforeOrEqual('price')
                                        ->live(onBlur: true),

                                    TextInput::make('sku')
                                        ->label('SKU')
                                        ->placeholder('مثال: TSHIRT-BLK-L')
                                        ->rule(function (?Product $record) {
                                            return Rule::unique('product_variants', 'sku')
                                                ->ignore($record?->defaultVariant?->id);
                                        })
                                        ->hintIcon(Heroicon::QuestionMarkCircle, 'در صورت خالی بودن، به صورت خودکار تولید می‌شود'),
                                ]),

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
                                    ]),
                            ])
                            ->visible(fn(Get $get) => $get('type') === ProductType::Simple),

                        Section::make('ویژگی‌ها')
                            ->schema([
                                Repeater::make('attributes')
                                    ->hiddenLabel()
                                    ->schema([
                                        Hidden::make('attribute_type')
                                            ->default(null),

                                        Select::make('attribute_id')
                                            ->label('ویژگی')
                                            ->options(static function () {
                                                return Attribute::query()
                                                    ->where('is_variant', false)
                                                    ->pluck('name', 'id');
                                            })
                                            ->reactive()
                                            ->afterStateUpdated(static function ($state, Set $set) {
                                                $type = Attribute::query()->find($state)?->type;

                                                $set('attribute_type', $type);
                                                $set('attribute_option_id', null);
                                                $set('attribute_option_ids', []);
                                            })
                                            ->required(),

                                        Select::make('attribute_option_id')
                                            ->label('مقدار')
                                            ->options(function (Get $get) {
                                                $attributeId = $get('attribute_id');
                                                if (!$attributeId) return [];

                                                return AttributeOption::query()
                                                    ->where('attribute_id', $attributeId)
                                                    ->pluck('label', 'id');
                                            })
                                            ->required()
                                            ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::Select),

                                        Select::make('attribute_option_ids')
                                            ->label('مقادیر')
                                            ->multiple()
                                            ->options(static function (Get $get) {
                                                $attributeId = $get('attribute_id');
                                                if (!$attributeId) return [];

                                                return AttributeOption::query()
                                                    ->where('attribute_id', $attributeId)
                                                    ->pluck('label', 'id');
                                            })
                                            ->required()
                                            ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::MultiSelect),

                                        TextInput::make('value_string')
                                            ->label('مقدار')
                                            ->required()
                                            ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::Text),

                                        Textarea::make('value_text')
                                            ->label('مقدار')
                                            ->rows(3)
                                            ->required()
                                            ->visible(static fn(Get $get) => $get('attribute_type') === AttributeType::Textarea),

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
                                    ->default([])
                                    ->addActionLabel('افزودن ویژگی'),
                            ]),

                        Section::make('تصاویر و مدیا')
                            ->schema([
                                Tabs::make('Media')
                                    ->tabs([
                                        Tab::make('تصاویر محصول')
                                            ->icon(Heroicon::OutlinedPhoto)
                                            ->schema([
                                                SpatieMediaLibraryFileUpload::make('images')
                                                    ->hiddenLabel()
                                                    ->collection('product_gallery')
                                                    ->multiple()
                                                    ->reorderable()
                                                    ->image()
                                                    ->imageEditor()
                                                    ->responsiveImages()
                                                    ->imagePreviewHeight(150)
                                                    ->panelLayout('grid')
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                    ->maxFiles(15)
                                                    ->helperText('اولین تصویر به عنوان تصویر شاخص (کاور) محصول استفاده می‌شود.'),
                                            ]),

                                        Tab::make('ویدیوهای محصول')
                                            ->icon(Heroicon::OutlinedFilm)
                                            ->schema([
                                                SpatieMediaLibraryFileUpload::make('videos')
                                                    ->hiddenLabel()
                                                    ->collection('product_videos')
                                                    ->maxSize(50 * 1024)
                                                    ->multiple()
                                                    ->reorderable()
                                                    ->maxFiles(5)
                                                    ->acceptedFileTypes(['video/mp4', 'video/webm'])
                                                    ->helperText('می‌توانید چندین ویدیو آپلود کنید. فرمت‌های مجاز: MP4, WebM (حداکثر ۵۰ مگابایت)'),
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Section::make('بهینه‌سازی موتورهای جستجو (SEO)')
                            ->description('تنظیمات متا تگ‌ها برای گوگل و سایر موتورها')
                            ->icon(Heroicon::OutlinedGlobeAlt)
                            ->collapsed()
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('عنوان سئو (Title)')
                                    ->hintIcon(Heroicon::QuestionMarkCircle, 'پیشنهاد: حداکثر ۶۰ کاراکتر. اگر خالی باشد، از نام محصول استفاده می‌شود.')
                                    ->maxLength(60)
                                    ->placeholder(fn(Get $get) => $get('name'))
                                    ->columnSpanFull(),

                                Textarea::make('seo_description')
                                    ->label('توضیحات سئو (Meta Description)')
                                    ->hintIcon(Heroicon::QuestionMarkCircle, 'پیشنهاد: حداکثر ۱۶۰ کاراکتر. خلاصه‌ای جذاب برای نمایش در نتایج گوگل.')
                                    ->maxLength(160)
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('وضعیت')
                            ->schema([
                                ShopForm::status('is_active', 'منتشر شده', 'آیا این محصول در سایت قابل مشاهده باشد؟'),

                                Toggle::make('manage_stock')
                                    ->label('مدیریت موجودی')
                                    ->default(true),

                                Toggle::make('is_virtual')
                                    ->label('محصول مجازی')
                                    ->onColor('info')
                                    ->offColor('gray')
                                    ->onIcon(Heroicon::OutlinedCloud)
                                    ->offIcon(Heroicon::OutlinedCube)
                                    ->default(false)
                                    ->reactive()
                                    ->hintIcon(
                                        Heroicon::QuestionMarkCircle,
                                        'محصول فیزیکی نیست و نیاز به ارسال ندارد (مثل خدمات، اشتراک، دوره آنلاین).'
                                    )
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (!$state) {
                                            $set('is_downloadable', false);
                                        }
                                    }),

                                Toggle::make('is_downloadable')
                                    ->label('محصول دانلودی')
                                    ->onColor('primary')
                                    ->offColor('gray')
                                    ->onIcon(Heroicon::OutlinedArrowDownTray)
                                    ->offIcon(Heroicon::OutlinedDocument)
                                    ->default(false)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $set('is_virtual', true);
                                        }
                                    })
                                    ->hintIcon(
                                        Heroicon::QuestionMarkCircle,
                                        'پس از خرید، مشتری می‌تواند فایل دانلود کند (مثل PDF، نرم‌افزار، فایل آموزشی).'
                                    ),

                                Select::make('out_of_stock_action')
                                    ->label('رفتار در ناموجودی')
                                    ->options(ProductOutOfStockAction::class)
                                    ->default(ProductOutOfStockAction::Default)
                                    ->native(false)
                                    ->required()
                                    ->live(),

                                TextInput::make('custom_stock_text')
                                    ->label('متن جایگزین')
                                    ->placeholder('مثلاً: تماس بگیرید')
                                    ->visible(fn(Get $get) => $get('out_of_stock_action') === ProductOutOfStockAction::Text)
                                    ->required(fn(Get $get) => $get('out_of_stock_action') === ProductOutOfStockAction::Text),

                                Select::make('type')
                                    ->label('نوع محصول')
                                    ->options(ProductType::class)
                                    ->required()
                                    ->default(ProductType::Simple)
                                    ->live()
                                    ->disabledOn('edit'),

                                Select::make('status')
                                    ->label('وضعیت')
                                    ->options(ProductStatus::class)
                                    ->default(ProductStatus::Draft)
                                    ->required()
                                    ->live(),

                                Toggle::make('schedule_publish')
                                    ->label('انتشار زمان‌بندی شده')
                                    ->visible(fn($get) => $get('status') === ProductStatus::Published)
                                    ->live(),

                                DateTimePicker::make('published_at')
                                    ->label('زمان انتشار')
                                    ->seconds(false)
                                    ->jalali()
                                    ->visible(fn($get) => $get('status') === ProductStatus::Published && $get('schedule_publish'))
                                    ->required(fn($get) => $get('schedule_publish')),
                            ]),

                        Section::make('سازماندهی')
                            ->schema([
                                Select::make('brand_id')
                                    ->label('برند محصول')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->label('نام برند')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                                                        $currentSlug = $get('slug');

                                                        if (blank($currentSlug) || SlugService::createSlug(Brand::class, 'slug', $old ?? '') === $currentSlug) {
                                                            $set('slug', SlugService::createSlug(Brand::class, 'slug', $state ?? ''));
                                                        }
                                                    }),

                                                ShopForm::slug(Brand::class),
                                            ]),
                                    ]),

                                Select::make('categories')
                                    ->label('دسته‌بندی‌ها')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable(),

                                Select::make('tags')
                                    ->label('برچسب‌ها')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}

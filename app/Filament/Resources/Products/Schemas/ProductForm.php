<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\AttributeType;
use App\Enums\ProductOutOfStockAction;
use App\Enums\ProductType;
use App\Filament\Components\ShopForm;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Brand;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductForm
{
    protected static function generateFieldComponent(Attribute $attribute)
    {
        // todo: check for file

        $attributeName = $attribute->code;

        $field = match ($attribute->type) {
            AttributeType::Select, AttributeType::Color => Select::make($attributeName)
                ->options($attribute->options->pluck('label', 'value'))
                ->searchable(),

            AttributeType::MultiSelect => Select::make($attributeName)
                ->options($attribute->options->pluck('label', 'value'))
                ->multiple()
                ->searchable(),

            AttributeType::Textarea => Textarea::make($attributeName)
                ->rows(3),

            AttributeType::Boolean => Toggle::make($attributeName)
                ->inline(false),

            AttributeType::Date => DatePicker::make($attributeName),

            AttributeType::Number => TextInput::make($attributeName)->numeric(),

            default => TextInput::make($attributeName),
        };

        return $field
            ->label($attribute->name)
            ->required($attribute->is_required);
    }

    protected static function getAttributeKeysForSet(?string $setId): array
    {
        if (!$setId) {
            return [];
        }

        return AttributeGroup::query()
            ->where('attribute_set_id', $setId)
            ->with(['attributes' => fn($query) => $query->select('attributes.id', 'attributes.code')])
            ->orderBy('position')
            ->get()
            ->pluck('attributes')
            ->flatten()
            ->pluck('code')
            ->toArray();
    }

    protected static function getDynamicAttributesSchema(?string $setId): array
    {
        if (!$setId) {
            return [];
        }

        $groups = AttributeGroup::query()
            ->where('attribute_set_id', $setId)
            ->with(['attributes.options'])
            ->orderBy('position')
            ->get();

        $schema = [];

        foreach ($groups as $group) {
            $groupFields = [];

            foreach ($group->attributes as $attribute) {
                $groupFields[] = self::generateFieldComponent($attribute);
            }

            if (empty($groupFields)) {
                continue;
            }

            $schema[] = Section::make($group->name)
                ->schema($groupFields)
                ->columns(2)
                ->collapsible()
                ->compact();
        }

        return $schema;
    }

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
                                    ->maxLength(255),

                                RichEditor::make('description')
                                    ->label('توضیحات کامل')
                                    ->columnSpanFull(),

                                Textarea::make('short_description')
                                    ->label('توضیحات کوتاه (خلاصه)')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Section::make('مشخصات فنی')
                            ->schema([
                                Select::make('attribute_set_id')
                                    ->label('مجموعه ویژگی')
                                    ->relationship('attributeSet', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $keys = self::getAttributeKeysForSet($state);

                                        $attrs = [];
                                        foreach ($keys as $key) {
                                            $attrs[$key] = null;
                                        }

                                        $set('attributes', $attrs);
                                    }),

                                Group::make()
                                    ->schema(fn(Get $get) => self::getDynamicAttributesSchema($get('attribute_set_id')))
                                    ->statePath('attributes')
                                    ->key(fn(Get $get) => 'attributes_group_' . $get('attribute_set_id'))
                                    ->columnSpanFull(),
                            ]),

                        Section::make('تصاویر و مدیا')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('thumbnail')
                                    ->label('تصویر شاخص (کاور)')
                                    ->collection('cover')
                                    ->image()
                                    ->imageEditor()
                                    ->required(),

                                Tabs::make('Media')
                                    ->tabs([
                                        Tab::make('تصاویر محصول')
                                            ->icon('heroicon-o-photo')
                                            ->schema([
                                                SpatieMediaLibraryFileUpload::make('images')
                                                    ->hiddenLabel()
                                                    ->collection('gallery')
                                                    ->multiple()
                                                    ->reorderable()
                                                    ->responsiveImages()
                                                    ->panelLayout('grid')
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                                            ]),

                                        Tab::make('ویدیو معرفی')
                                            ->icon('heroicon-o-film')
                                            ->schema([
                                                SpatieMediaLibraryFileUpload::make('videos')
                                                    ->label('ویدیو محصول')
                                                    ->collection('videos')
                                                    ->maxSize(50 * 1024)
                                                    ->acceptedFileTypes(['video/mp4', 'video/webm'])
                                                    ->helperText('فرمت‌های مجاز: MP4, WebM (حداکثر ۵۰ مگابایت)'),
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Section::make('قیمت‌گذاری و موجودی')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('sku')
                                            ->label('کد محصول (SKU)')
                                            ->default(fn() => 'PRD-' . strtoupper(Str::random(6)))
                                            ->unique(ignoreRecord: true)
                                            ->required(),

                                        TextInput::make('price')
                                            ->label('قیمت اصلی')
                                            ->numeric()
                                            ->mask(RawJs::make('$money($input)'))
                                            ->prefix('تومان')
                                            ->maxValue(999999999999)
                                            ->extraAttributes(['dir' => 'ltr'])
                                            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),

                                        TextInput::make('sale_price')
                                            ->label('قیمت شگفت‌انگیز')
                                            ->numeric()
                                            ->mask(RawJs::make('$money($input)'))
                                            ->prefix('تومان')
                                            ->lte('price')
                                            ->extraAttributes(['dir' => 'ltr'])
                                            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),
                                    ]),
                            ]),

                        Section::make('بهینه‌سازی موتورهای جستجو (SEO)')
                            ->description('تنظیمات متا تگ‌ها برای گوگل و سایر موتورها')
                            ->icon('heroicon-o-globe-alt')
                            ->collapsed()
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('عنوان سئو (Title)')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'پیشنهاد: حداکثر ۶۰ کاراکتر. اگر خالی باشد، از نام محصول استفاده می‌شود.')
                                    ->maxLength(60)
                                    ->placeholder(fn(Get $get) => $get('name'))
                                    ->columnSpanFull(),

                                Textarea::make('seo_description')
                                    ->label('توضیحات سئو (Meta Description)')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'پیشنهاد: حداکثر ۱۶۰ کاراکتر. خلاصه‌ای جذاب برای نمایش در نتایج گوگل.')
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
                                    ->default(true)
                                    ->required(),

                                Select::make('out_of_stock_action')
                                    ->label('رفتار در ناموجودی')
                                    ->options(ProductOutOfStockAction::class)
                                    ->default(ProductOutOfStockAction::Default)
                                    ->native(false)
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
                                    ->live(),
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

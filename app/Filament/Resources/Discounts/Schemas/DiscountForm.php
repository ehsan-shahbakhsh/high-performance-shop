<?php

namespace App\Filament\Resources\Discounts\Schemas;

use App\Enums\{DiscountConditionMatchType,
    DiscountRuleType,
    DiscountScope,
    DiscountStrategy,
    DiscountType
};
use App\Filament\Components\ShopForm;
use App\Models\{Brand, Product, ProductCategory, ProductVariant, User};
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\{Fieldset, Grid, Section};
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Tags\Tag;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات اصلی جشنواره')
                    ->description('نام، تاریخ‌ها و رفتار اصلی سیستم در قبال این تخفیف را تعیین کنید.')
                    ->schema([
                        Grid::make(1)->schema([
                            TextInput::make('name')
                                ->label('نام جشنواره / تخفیف')
                                ->placeholder('مثلاً: حراج آخر فصل')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('priority')
                                ->label('اولویت اعمال')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->minValue(0)
                                ->hintIcon(Heroicon::QuestionMarkCircle, 'اولویت بالاتر زودتر بررسی و اعمال می‌شود.'),

                            Toggle::make('is_automatic')
                                ->label('اعمال خودکار (بدون کد)')
                                ->default(false)
                                ->hintIcon(Heroicon::QuestionMarkCircle, 'در صورت فعال بودن، نیازی به تولید کوپن نیست و روی سبدهای واجد شرایط اعمال می‌شود.')
                                ->inline(false),

                            Toggle::make('is_exclusive')
                                ->label('تخفیف انحصاری')
                                ->default(false)
                                ->hintIcon(Heroicon::QuestionMarkCircle, 'اگر اعمال شود، سایر تخفیف‌ها روی سبد خرید کاربر لغو خواهند شد.')
                                ->inline(false),
                        ]),

                        Grid::make(2)->schema([
                            DateTimePicker::make('starts_at')
                                ->jalali()
                                ->label('تاریخ شروع')
                                ->native(false)
                                ->seconds(false)
                                ->displayFormat('Y/m/d H:i'),

                            DateTimePicker::make('ends_at')
                                ->jalali()
                                ->label('تاریخ پایان')
                                ->native(false)
                                ->seconds(false)
                                ->displayFormat('Y/m/d H:i')
                                ->after('starts_at'),
                        ]),
                    ]),

                Section::make('مقدار و تنظیمات تخفیف')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('type')
                                ->label('نوع محاسبه')
                                ->options(static function (Get $get) {
                                    return match ($get('scope')) {
                                        DiscountScope::Order => [
                                            DiscountType::Fixed->value => DiscountType::Fixed->getLabel(),
                                            DiscountType::Percentage->value => DiscountType::Percentage->getLabel(),
                                        ],
                                        DiscountScope::Item => [
                                            DiscountType::Fixed->value => DiscountType::Fixed->getLabel(),
                                            DiscountType::Percentage->value => DiscountType::Percentage->getLabel(),
                                            DiscountType::BuyXGetY->value => DiscountType::BuyXGetY->getLabel(),
                                        ],
                                        DiscountScope::Shipping => [
                                            DiscountType::Fixed->value => DiscountType::Fixed->getLabel(),
                                            DiscountType::Percentage->value => DiscountType::Percentage->getLabel(),
                                            DiscountType::FreeShipping->value => DiscountType::FreeShipping->getLabel(),
                                        ],
                                        default => [],
                                    };
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(static fn($state, Set $set) => match ($state) {
                                    DiscountType::Fixed->value => $set('max_discount_amount', null),
                                    default => null,
                                }),

                            TextInput::make('amount')
                                ->label(static fn(Get $get) => $get('type') === DiscountType::Percentage->value ? 'درصد تخفیف' : 'مبلغ تخفیف')
                                ->numeric()
                                ->required()
                                ->extraAttributes(['dir' => 'ltr'])
                                ->visible(static fn(Get $get) => in_array($get('type'), [DiscountType::Percentage->value, DiscountType::Fixed->value]))
                                ->prefix(static fn(Get $get) => $get('type') === DiscountType::Percentage->value ? '%' : 'تومان')
                                ->maxValue(static fn(Get $get) => $get('type') === DiscountType::Percentage->value ? 100 : null),

                            TextInput::make('max_discount_amount')
                                ->label('سقف تخفیف (تومان)')
                                ->numeric()
                                ->visible(static fn(Get $get) => $get('type') === DiscountType::Percentage->value)
                                ->placeholder('بی‌نهایت'),
                        ]),

                        Fieldset::make('تنظیمات طرح یکی بخر یکی ببر')
                            ->visible(static fn(Get $get) => $get('type') === DiscountType::BuyXGetY->value)
                            ->schema([
                                TextInput::make('action_settings.bogo.buy_qty')
                                    ->label('تعداد خرید (X)')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),

                                TextInput::make('action_settings.bogo.get_qty')
                                    ->label('تعداد هدیه (Y)')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),

                                TextInput::make('action_settings.bogo.discount_percent')
                                    ->label('درصد تخفیف روی کالای هدیه')
                                    ->helperText('۱۰۰٪ یعنی کالای دوم کاملا رایگان است')
                                    ->default(100)
                                    ->numeric()
                                    ->maxValue(100)
                                    ->required(),

                                Select::make('action_settings.bogo.strategy')
                                    ->label('کالای هدیه چیست؟')
                                    ->live()
                                    ->options(DiscountStrategy::class)
                                    ->required(),

                                Select::make('action_settings.bogo.target_variant_id')
                                    ->label('انتخاب کالای هدیه (دقیق)')
                                    ->searchable()
                                    ->getSearchResultsUsing(static function (string $search) {
                                        return ProductVariant::query()
                                            ->with('product:id,name')
                                            ->where('sku', 'like', "%$search%")
                                            ->orWhereHas('product', static fn($q) => $q->where('name', 'like', "%$search%"))
                                            ->limit(20)
                                            ->get()
                                            ->mapWithKeys(static fn($v) => [$v->id => "{$v->product->name} ({$v->sku})"]);
                                    })
                                    ->getOptionLabelUsing(static function ($value) {
                                        return ProductVariant::with('product')->find($value)?->product->name;
                                    })
                                    ->visible(static fn(Get $get) => $get('action_settings.bogo.strategy') === DiscountStrategy::Specific)
                                    ->required(static fn(Get $get) => $get('action_settings.bogo.strategy') === DiscountStrategy::Specific),
                            ])
                            ->columns(2),

                        Grid::make(2)->schema([
                            TextInput::make('usage_limit')
                                ->label('سقف کل استفاده')
                                ->numeric()
                                ->placeholder('مثلاً ۱۰۰ بار'),

                            TextInput::make('user_usage_limit')
                                ->label('سقف استفاده هر کاربر')
                                ->numeric()
                                ->default(1),
                        ]),

                        ShopForm::status('is_active', 'وضعیت فعال بودن'),
                    ]),

                Section::make('قلمرو و محدوده اعمال')
                    ->description('مشخص کنید تخفیف روی چه کالاهایی و برای چه کسانی اعمال شود.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('scope')
                                ->label('قلمرو تخفیف')
                                ->options(DiscountScope::class)
                                ->hintIcon(Heroicon::QuestionMarkCircle, 'اگر "کل سبد خرید" باشد، نیازی به انتخاب محصول نیست، مگر برای محدود کردن کاربر.')
                                ->required()
                                ->live(),
                        ]),

                        Repeater::make('discountables')
                            ->relationship()
                            ->label('شرایط شمول و عدم شمول')
                            ->helperText('محصولات، دسته‌ها یا کاربران خاصی را اضافه کنید.')
                            ->schema([
                                Grid::make(3)->schema([
                                    MorphToSelect::make('discountable')
                                        ->label('انتخاب هدف')
                                        ->types([
                                            MorphToSelect\Type::make(Product::class)
                                                ->label('محصول خاص')
                                                ->titleAttribute('name'),

                                            MorphToSelect\Type::make(ProductCategory::class)
                                                ->label('دسته‌بندی')
                                                ->titleAttribute('name'),

                                            MorphToSelect\Type::make(Brand::class)
                                                ->label('برند')
                                                ->titleAttribute('name'),
                                        ])
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(2),

                                    Toggle::make('is_excluded')
                                        ->label('محروم کردن؟')
                                        ->onColor('danger')
                                        ->offColor('success')
                                        ->onIcon(Heroicon::Check)
                                        ->offIcon(Heroicon::XMark)
                                        ->inline(false)
                                        ->hintIcon(Heroicon::QuestionMarkCircle, 'اگر روشن باشد، این آیتم شامل تخفیف نمی‌شود (لیست سیاه).')
                                        ->default(false),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('افزودن شرط جدید (محصول/دسته‌بندی)')
                            ->columnSpanFull()
                            ->visible(static fn(Get $get) => $get('type') === DiscountScope::Item),
                    ]),

                Section::make('قوانین و شروط تخفیف')
                    ->description('تعیین کنید این تخفیف چه زمانی معتبر است.')
                    ->schema([
                        Select::make('condition_match_type')
                            ->label('نحوه بررسی شروط')
                            ->options(DiscountConditionMatchType::class)
                            ->default(DiscountConditionMatchType::All)
                            ->required()
                            ->selectablePlaceholder(false)
                            ->columnSpanFull(),

                        Repeater::make('rules')
                            ->relationship()
                            ->label('لیست شروط')
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('type')
                                        ->label('نوع شرط')
                                        ->options(DiscountRuleType::class)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(static function (Set $set) {
                                            $set('value_string', null);
                                            $set('value_integer', null);
                                            $set('value_float', null);
                                            $set('value_boolean', null);
                                            $set('value_json', null);
                                        })
                                        ->columnSpan(2),

                                    Select::make('operator')
                                        ->label('عملگر')
                                        ->required()
                                        ->options(static fn(Get $get) => $get('type')?->allowedOperators())
                                        ->columnSpan(1),

                                    Hidden::make('value_json')
                                        ->default(null)
                                        ->afterStateHydrated(static function ($state, Set $set, Get $get) {
                                            if ($state) {
                                                if ($get('type') === DiscountRuleType::TimeRange) {
                                                    $set('time_range', $state);
                                                } else if ($get('type') === DiscountRuleType::DayOfWeek) {
                                                    $set('value_json_days', $state);
                                                } else if (in_array($get('type'), [
                                                    DiscountRuleType::CartContainsProduct,
                                                    DiscountRuleType::CartContainsCategory,
                                                    DiscountRuleType::CartContainsBrand,
                                                    DiscountRuleType::UserId,
                                                ])) {
                                                    $set('value_json_select', $state);
                                                }
                                            }
                                        }),

                                    TextInput::make('value_integer')
                                        ->label('مقدار عددی')
                                        ->numeric()
                                        ->required()
                                        ->visible(static fn(Get $get) => in_array($get('type'), [
                                            DiscountRuleType::CartSubtotal,
                                            DiscountRuleType::CartQuantity,
                                            DiscountRuleType::CartWeight,
                                            DiscountRuleType::TotalSpent,
                                            DiscountRuleType::OrderCount,
                                        ]))
                                        ->columnSpan(1),

                                    CheckboxList::make('value_json_days')
                                        ->label('روزهای هفته')
                                        ->options([
                                            0 => 'شنبه',
                                            1 => 'یکشنبه',
                                            2 => 'دوشنبه',
                                            3 => 'سه‌شنبه',
                                            4 => 'چهارشنبه',
                                            5 => 'پنجشنبه',
                                            6 => 'جمعه',
                                        ])
                                        ->columns(2)
                                        ->required()
                                        ->columnSpanFull()
                                        ->visible(static fn(Get $get) => $get('type') === DiscountRuleType::DayOfWeek)
                                        ->afterStateUpdated(static fn(Set $set, $state) => $set('value_json', $state)),

                                    Select::make('value_json_select')
                                        ->label('انتخاب آیتم‌ها')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->visible(static fn(Get $get) => in_array($get('type'), [
                                            DiscountRuleType::CartContainsProduct,
                                            DiscountRuleType::CartContainsCategory,
                                            DiscountRuleType::CartContainsBrand,
                                            DiscountRuleType::UserId,
                                        ]))
                                        ->getSearchResultsUsing(static fn(string $search, Get $get) => match ($get('type')) {
                                            DiscountRuleType::CartContainsProduct => Product::query()
                                                ->where('name', 'like', "%{$search}%")
                                                ->limit(20)->pluck('name', 'id'),
                                            DiscountRuleType::CartContainsCategory => ProductCategory::query()
                                                ->where('name', 'like', "%{$search}%")
                                                ->limit(20)->pluck('name', 'id'),
                                            DiscountRuleType::CartContainsBrand => Brand::query()
                                                ->where('name', 'like', "%{$search}%")
                                                ->limit(20)->pluck('name', 'id'),
                                            DiscountRuleType::UserId => User::query()
                                                ->selectRaw("id, CONCAT(CONCAT_WS(' ', first_name, last_name), ' (', COALESCE(mobile, email), ')') as label")
                                                ->where(static function ($query) use ($search) {
                                                    $query->whereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$search}%"])
                                                        ->orWhere('mobile', 'like', "%{$search}%")
                                                        ->orWhere('email', 'like', "%{$search}%");
                                                })
                                                ->limit(20)
                                                ->pluck('label', 'id'),
                                            default => [],
                                        })
                                        ->getOptionLabelsUsing(static fn(array $values, Get $get) => match ($get('type')) {
                                            DiscountRuleType::CartContainsProduct => Product::query()->findMany($values)->pluck('name', 'id'),
                                            DiscountRuleType::CartContainsCategory => ProductCategory::query()->findMany($values)->pluck('name', 'id'),
                                            DiscountRuleType::CartContainsBrand => Brand::query()->findMany($values)->pluck('name', 'id'),
                                            DiscountRuleType::UserId => User::query()
                                                ->whereIn('id', $values)
                                                ->selectRaw("id, CONCAT(CONCAT_WS(' ', first_name, last_name), ' (', COALESCE(mobile, email), ')') as label")
                                                ->pluck('label', 'id'),
                                            default => [],
                                        })
                                        ->columnSpanFull()
                                        ->afterStateUpdated(static fn(Set $set, $state) => $set('value_json', $state)),

//                                    Select::make('value_json')
//                                        ->label('انتخاب تگ‌های کاربر')
//                                        ->multiple()
//                                        ->searchable()
//                                        ->required()
//                                        ->visible(static fn(Get $get) => $get('type') === DiscountRuleType::UserGroup)
//                                        ->options(static fn() => Tag::getWithType('user_groups')->pluck('name', 'id'))
//                                        ->columnSpan(1),

                                    Toggle::make('value_boolean')
                                        ->label('وضعیت')
                                        ->inline(false)
                                        ->visible(static fn(Get $get) => $get('type') === DiscountRuleType::IsFirstOrder)
                                        ->columnSpan(1),

                                    Grid::make(2)
                                        ->visible(static fn(Get $get) => $get('type') === DiscountRuleType::TimeRange)
                                        ->schema([
                                            TimePicker::make('time_range.start')
                                                ->label('از ساعت')
                                                ->required()
                                                ->seconds(false)
                                                ->displayFormat('H:i')
                                                ->format('H:i')
                                                ->live()
                                                ->afterStateUpdated(static function ($state, Set $set) {
                                                    $set('value_json.start', $state);
                                                }),

                                            TimePicker::make('time_range.end')
                                                ->label('تا ساعت')
                                                ->required()
                                                ->seconds(false)
                                                ->displayFormat('H:i')
                                                ->format('H:i')
                                                ->live()
                                                ->after('time_range.start')
                                                ->afterStateUpdated(static function ($state, Set $set) {
                                                    $set('value_json.end', $state);
                                                }),
                                        ])
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('افزودن شرط جدید'),
                    ]),
            ]);
    }
}

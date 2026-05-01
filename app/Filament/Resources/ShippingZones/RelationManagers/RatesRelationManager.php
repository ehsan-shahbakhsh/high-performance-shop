<?php

namespace App\Filament\Resources\ShippingZones\RelationManagers;

use App\Enums\Shipping\{ConditionType, MatchType, Operator};
use App\Filament\Components\{ShopForm, ShopTable};
use App\Models\{Brand, Product, ProductCategory};
use Filament\Actions\{BulkActionGroup, CreateAction, DeleteAction, DeleteBulkAction, EditAction};
use Filament\Forms\Components\{Hidden, Repeater, Select, TextInput};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\{Grid, Section, Utilities\Set};
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    protected static ?string $modelLabel = 'تعرفه ارسال';
    protected static ?string $pluralLabel = 'تعرفه‌های ارسال';

    protected static ?string $title = 'مدیریت تعرفه‌ها';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات پایه')
                    ->schema([
                        Select::make('shipping_method_id')
                            ->relationship('shippingMethod', 'name')
                            ->label('روش ارسال')
                            ->required()
                            ->searchable()
                            ->preload(),
//                            ->disableOptionWhen(fn($value, $state, Get $get) => $get('shipping_method_id') === $value),

                        ShopForm::status('is_active', 'فعال در این منطقه')->columnSpanFull(),
                    ]),

                Section::make('محاسبه هزینه (تومان)')
                    ->schema([
                        ShopForm::price('base_price', 'هزینه پایه'),

                        ShopForm::price('price_per_kg', 'هزینه هر کیلو اضافه', isRequired: false)
                            ->helperText('اگر خالی باشد، فقط هزینه پایه محاسبه می‌شود.'),

                        ShopForm::price('free_shipping_over', 'رایگان برای سفارش‌های بالای...', isRequired: false),

                        ShopForm::price('cod_fee', 'هزینه اضافه پرداخت در محل', isRequired: false)
                            ->default(0),
                    ]),

                Section::make('شروط اعمال (محدودیت‌ها)')
                    ->description('این تعرفه فقط در شرایط زیر اعمال می‌شود (خالی = بدون محدودیت)')
                    ->collapsed()
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('min_weight')
                                    ->label('حداقل وزن (گرم)')
                                    ->numeric(),
                                TextInput::make('max_weight')
                                    ->label('حداکثر وزن (گرم)')
                                    ->numeric(),

                                ShopForm::price('min_subtotal', 'حداقل مبلغ سفارش', isRequired: false),
                                ShopForm::price('max_subtotal', 'حداکثر مبلغ سفارش', isRequired: false),
                            ]),
                    ]),

                Section::make('بازنویسی زمان تحویل (اختیاری)')
                    ->description('اگر پر شود، جایگزین زمان پیش‌فرض روش ارسال می‌شود.')
                    ->collapsed()
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('min_virtual')
                                    ->label('حداقل زمان')
                                    ->numeric()
                                    ->requiredWith('max_virtual')
                                    ->live()
                                    ->suffix(static fn(Get $get) => match ((int)$get('unit_virtual')) {
                                        1440 => 'روز',
                                        60 => 'ساعت',
                                        default => 'دقیقه',
                                    })
                                    ->dehydrated(false)
                                    ->afterStateHydrated(static function ($component, Get $get, ?Model $record) {
                                        if (!$record || $record->min_delivery_time === null) return;

                                        $min = $record->min_delivery_time;
                                        $max = $record->max_delivery_time ?? 0;

                                        $divider = 1;
                                        if ($min % 1440 === 0 && $max % 1440 === 0) $divider = 1440;
                                        elseif ($min % 60 === 0 && $max % 60 === 0) $divider = 60;

                                        $component->state($min / $divider);
                                    }),

                                TextInput::make('max_virtual')
                                    ->label('حداکثر زمان')
                                    ->numeric()
                                    ->requiredWith('min_virtual')
                                    ->live()
                                    ->suffix(static fn(Get $get) => match ((int)$get('unit_virtual')) {
                                        1440 => 'روز',
                                        60 => 'ساعت',
                                        default => 'دقیقه',
                                    })
                                    ->dehydrated(false)
                                    ->afterStateHydrated(static function ($component, ?Model $record) {
                                        if (!$record || $record->max_delivery_time === null) return;

                                        $min = $record->min_delivery_time ?? 0;
                                        $max = $record->max_delivery_time;

                                        $divider = 1;
                                        if ($min % 1440 === 0 && $max % 1440 === 0) $divider = 1440;
                                        elseif ($min % 60 === 0 && $max % 60 === 0) $divider = 60;

                                        $component->state($max / $divider);
                                    }),

                                Select::make('unit_virtual')
                                    ->label('واحد زمانی')
                                    ->options([
                                        1 => 'دقیقه',
                                        60 => 'ساعت',
                                        1440 => 'روز',
                                    ])
                                    ->default(1)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(static function ($component, ?Model $record) {
                                        if (!$record) return;

                                        $min = $record->min_delivery_time ?? 0;
                                        $max = $record->max_delivery_time ?? 0;

                                        if ($min > 0 && $max > 0) {
                                            if ($min % 1440 === 0 && $max % 1440 === 0) {
                                                $component->state(1440);
                                            } elseif ($min % 60 === 0 && $max % 60 === 0) {
                                                $component->state(60);
                                            } else {
                                                $component->state(1);
                                            }
                                        }
                                    }),

                                Hidden::make('min_delivery_time')
                                    ->dehydrateStateUsing(static fn(Get $get) => (int)$get('min_virtual') * (int)$get('unit_virtual')),

                                Hidden::make('max_delivery_time')
                                    ->dehydrateStateUsing(static fn(Get $get) => (int)$get('max_virtual') * (int)$get('unit_virtual')),
                            ])
                    ]),

                Section::make('شرایط پیشرفته (Conditions)')
                    ->description('تعریف شروط خاص برای اعمال این نرخ ارسال (مثلاً: در صورتی که تعداد اقلام سبد خرید بیشتر از ۳ باشد)')
                    ->schema([
                        Select::make('conditions.matchType')
                            ->label('نوع تطابق شروط')
                            ->options(MatchType::class)
                            ->default(MatchType::All)
                            ->selectablePlaceholder(false)
                            ->required(),

                        Repeater::make('conditions.rules')
                            ->label('قوانین')
                            ->addActionLabel('افزودن شرط جدید')
                            ->defaultItems(0)
                            ->columns(3)
                            ->schema([
                                Select::make('type')
                                    ->label('نوع شرط')
                                    ->options(ConditionType::class)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(static function (Set $set) {
                                        $set('value_id', null);
                                        $set('value_ids', null);
                                        $set('value', null);
                                    }),

                                Select::make('operator')
                                    ->label('عملگر')
                                    ->options(static function (Get $get) {
                                        return match ($get('type')) {
                                            ConditionType::ItemsCount => Operator::numericOptions(),
                                            default => Operator::relationOptions(),
                                        };
                                    })
                                    ->required()
                                    ->live(),

                                Select::make('value_id')
                                    ->label('مقدار')
                                    ->required()
                                    ->options(static function (Get $get) {
                                        return match ($get('type')) {
                                            ConditionType::Categories => ProductCategory::query()->pluck('name', 'id'),
                                            ConditionType::Products => Product::query()->pluck('name', 'id'),
                                            ConditionType::Brands => Brand::query()->pluck('name', 'id'),
                                            default => [],
                                        };
                                    })
                                    ->searchable()
                                    ->visible(static function (Get $get): bool {
                                        if ($get('type') === ConditionType::ItemsCount) return false;

                                        if (in_array($get('operator'), [Operator::In->value, Operator::NotIn->value])) return false;

                                        return true;
                                    }),

                                Select::make('value_ids')
                                    ->label('مقادیر')
                                    ->required()
                                    ->multiple()
                                    ->options(static function (Get $get) {
                                        return match ($get('type')) {
                                            ConditionType::Categories => ProductCategory::query()->pluck('name', 'id'),
                                            ConditionType::Products => Product::query()->pluck('name', 'id'),
                                            ConditionType::Brands => Brand::query()->pluck('name', 'id'),
                                            default => [],
                                        };
                                    })
                                    ->searchable()
                                    ->visible(static function (Get $get): bool {
                                        if ($get('type') === ConditionType::ItemsCount) return false;

                                        if (!in_array($get('operator'), [Operator::In->value, Operator::NotIn->value])) return false;

                                        return true;
                                    }),

                                TextInput::make('value')
                                    ->label('مقدار')
                                    ->numeric()
                                    ->required()
                                    ->visible(static fn(Get $get) => $get('type') === ConditionType::ItemsCount),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->modifyQueryUsing(static fn($query) => $query->with('shippingMethod'))
            ->columns([
                ShopTable::id(),

                TextColumn::make('shippingMethod.name')
                    ->label('روش ارسال')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('base_price')
                    ->label('هزینه پایه')
                    ->formatStateUsing(static fn($state) => number_format($state) . ' تومان')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('free_shipping_over')
                    ->label('سقف رایگان')
                    ->formatStateUsing(static fn($state) => number_format($state) . ' تومان')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('weight_limit')
                    ->label('محدودیت وزن')
                    ->state(static fn(Model $record) => ($record->min_weight ? $record->min_weight . ' تا ' : 'تا ') .
                        ($record->max_weight ? $record->max_weight . ' گرم' : '∞')
                    )
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                ShopTable::position(),

                ShopTable::status(),

                ShopTable::createdAt(),
                ShopTable::updatedAt(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('افزودن تعرفه جدید')
                    ->modalWidth('4xl'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->modalWidth('4xl'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

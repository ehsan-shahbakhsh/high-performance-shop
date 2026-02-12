<?php

namespace App\Filament\Resources\ShippingZones\RelationManagers;

use App\Filament\Components\ShopForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
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
                        TextInput::make('base_price')
                            ->label('هزینه پایه')
                            ->required()
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('تومان')
                            ->maxValue(999999999999)
                            ->extraAttributes(['dir' => 'ltr'])
                            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),

                        TextInput::make('price_per_kg')
                            ->label('هزینه هر کیلو اضافه')
                            ->numeric()
                            ->helperText('اگر خالی باشد، فقط هزینه پایه محاسبه می‌شود.')
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('تومان')
                            ->maxValue(999999999999)
                            ->extraAttributes(['dir' => 'ltr'])
                            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),

                        TextInput::make('free_shipping_over')
                            ->label('رایگان برای سفارش‌های بالای...')
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('تومان')
                            ->maxValue(999999999999)
                            ->extraAttributes(['dir' => 'ltr'])
                            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),

                        TextInput::make('cod_fee')
                            ->label('هزینه اضافه پرداخت در محل')
                            ->numeric()
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('تومان')
                            ->maxValue(999999999999)
                            ->extraAttributes(['dir' => 'ltr'])
                            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),
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

                                TextInput::make('min_subtotal')
                                    ->label('حداقل مبلغ سفارش')
                                    ->numeric()
                                    ->columnSpanFull()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->prefix('تومان')
                                    ->maxValue(999999999999)
                                    ->extraAttributes(['dir' => 'ltr'])
                                    ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),
                                TextInput::make('max_subtotal')
                                    ->label('حداکثر مبلغ سفارش')
                                    ->numeric()
                                    ->columnSpanFull()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->prefix('تومان')
                                    ->maxValue(999999999999)
                                    ->extraAttributes(['dir' => 'ltr'])
                                    ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),
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
                                    ->suffix(fn (Get $get) => match ((int)$get('unit_virtual')) {
                                        1440 => 'روز',
                                        60 => 'ساعت',
                                        default => 'دقیقه',
                                    })
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, Get $get, ?Model $record) {
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
                                    ->suffix(fn (Get $get) => match ((int)$get('unit_virtual')) {
                                        1440 => 'روز',
                                        60 => 'ساعت',
                                        default => 'دقیقه',
                                    })
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, ?Model $record) {
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
                                    ->afterStateHydrated(function ($component, ?Model $record) {
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
                                    ->dehydrateStateUsing(fn (Get $get) => (int)$get('min_virtual') * (int)$get('unit_virtual')),

                                Hidden::make('max_delivery_time')
                                    ->dehydrateStateUsing(fn (Get $get) => (int)$get('max_virtual') * (int)$get('unit_virtual')),
                            ])
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->modifyQueryUsing(fn($query) => $query->with('shippingMethod'))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('shippingMethod.name')
                    ->label('روش ارسال')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('base_price')
                    ->label('هزینه پایه')
                    ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('free_shipping_over')
                    ->label('سقف رایگان')
                    ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('weight_limit')
                    ->label('محدودیت وزن')
                    ->state(fn(Model $record) => ($record->min_weight ? $record->min_weight . ' تا ' : 'تا ') .
                        ($record->max_weight ? $record->max_weight . ' گرم' : '∞')
                    )
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextInputColumn::make('position')
                    ->label('موقعیت')
                    ->rules(['required', 'int', 'min:1'])
                    ->type('number')
                    ->sortable()
                    ->width(80)
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('وضعیت')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ ایجاد')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ آخرین بروزرسانی')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
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

<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Components\ShopForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static string|null|\BackedEnum $icon = 'heroicon-o-swatch';

    protected static ?string $title = 'تنوع محصول (واریانت‌ها)';

    protected static ?string $modelLabel = 'تنوع';

    protected static ?string $pluralLabel = 'تنوع‌ها';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('جزئیات فنی و موجودی')
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->unique(ignoreRecord: true)
                            ->placeholder('مثلا: PROD-RED-XL'),

                        TextInput::make('stock_quantity')
                            ->label('موجودی')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        ShopForm::status('is_active', 'وضعیت فعال بودن'),
                    ])->columns(),

                Section::make('قیمت‌گذاری')
                    ->schema([
                        TextInput::make('price')
                            ->label('قیمت اصلی')
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('تومان')
                            ->maxValue(999999999999)
                            ->extraAttributes(['dir' => 'ltr'])
                            ->required(fn(RelationManager $livewire) => blank($livewire->getOwnerRecord()->price))
                            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state))
                            ->hintIcon('heroicon-m-question-mark-circle', 'اگر محصول اصلی قیمت ندارد، وارد کردن قیمت برای هر تنوع اجباری است.'),

                        TextInput::make('sale_price')
                            ->label('قیمت شگفت‌انگیز')
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix('تومان')
                            ->lte('price')
                            ->extraAttributes(['dir' => 'ltr'])
                            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state)),
                    ])->columns(),

                Section::make('ویژگی‌های تنوع')
                    ->description('ویژگی‌ها را به صورت کلید و مقدار وارد کنید (مثلا: color => red)')
                    ->schema([
                        KeyValue::make('attributes')
                            ->label('ویژگی‌ها')
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    ksort($state);
                                    $set('variant_hash', md5(json_encode($state)));
                                }
                            }),

                        Hidden::make('variant_hash')
                            ->required(),
                    ]),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('شناسنامه تنوع')
                    ->icon('heroicon-m-finger-print')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('sku')
                            ->label('کد انبار (SKU)')
                            ->weight('bold')
                            ->fontFamily('mono')
                            ->copyable()
                            ->icon('heroicon-m-qr-code'),

                        TextEntry::make('attributes')
                            ->label('ویژگی‌های محصول')
                            ->badge()
                            ->separator(',')
                            ->formatStateUsing(fn($state) => collect($state)
                                ->map(fn($value, $key) => "{$key}: {$value}")
                                ->values()
                            )
                            ->color('info')
                            ->columnSpan(2),
                    ]),

                Section::make('وضعیت انبار و قیمت‌گذاری')
                    ->icon('heroicon-m-currency-dollar')
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
                                $state <= 0 => 'heroicon-m-x-circle',
                                $state < 10 => 'heroicon-m-exclamation-triangle',
                                default => 'heroicon-m-check-circle',
                            }),
                    ]),

                Section::make('اطلاعات سیستمی')
                    ->icon('heroicon-m-cpu-chip')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        IconEntry::make('is_active')
                            ->label('وضعیت انتشار')
                            ->boolean(),

                        TextEntry::make('variant_hash')
                            ->label('هش یکتا (Variant Hash)')
                            ->fontFamily('mono')
                            ->copyable()
                            ->columnSpan(2)
                            ->hintIcon('heroicon-m-question-mark-circle', 'این کد برای جلوگیری از ساخت تنوع تکراری استفاده می‌شود.'),
                    ]),

                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاریخ ایجاد')
                            ->dateTime()
                            ->formatStateUsing(fn($state) => verta($state)->format('j F Y - H:i'))
                            ->color('gray'),

                        TextEntry::make('updated_at')
                            ->label('آخرین تغییر موجودی/قیمت')
                            ->dateTime()
                            ->formatStateUsing(fn($state) => verta($state)->format('j F Y - H:i'))
                            ->color('gray'),
                    ])->columns(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('attributes')
                    ->label('ویژگی‌ها')
                    ->badge()
                    ->formatStateUsing(fn($state) => collect($state)
                        ->map(fn($value, $key) => "{$key}: {$value}")
                        ->values()
                    )
                    ->toggleable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                TextInputColumn::make('stock_quantity')
                    ->label('موجودی')
                    ->sortable()
                    ->rules(['required', 'numeric', 'min:0'])
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('وضعیت')
                    ->onColor('success')
                    ->offColor('danger')
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
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ حذف')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت')
                    ->placeholder('همه')
                    ->trueLabel('فقط فعال‌ها')
                    ->falseLabel('فقط غیرفعال‌ها'),

                Filter::make('out_of_stock')
                    ->label('ناموجودها')
                    ->query(fn($query) => $query->where('stock_quantity', '<=', 0)),

                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

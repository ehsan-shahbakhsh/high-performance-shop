<?php

namespace App\Filament\Resources\Warehouses\RelationManagers;

use App\Enums\InventoryTransactionType;
use App\Models\Inventory;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class InventoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'inventories';

    protected static ?string $title = 'موجودی کالاها';

    protected static ?string $modelLabel = 'موجودی کالا';

    protected static ?string $pluralLabel = 'موجودی کالاها';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('انتخاب محصول')
                    ->description('کالایی که می‌خواهید به این انبار اضافه کنید.')
                    ->schema([
                        Select::make('product_variant_id')
                            ->label('محصول / تنوع')
                            ->relationship(
                                name: 'productVariant',
                                titleAttribute: 'sku',
                                modifyQueryUsing: fn($query) => $query->with('product')
                            )
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->product->name} - " .
                                collect($record->attributes)->values()->join('/') .
                                " ({$record->sku})"
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rule(function (RelationManager $livewire) {
                                return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                    $exists = $livewire->getRelationship()->where('product_variant_id', $value)->exists();
                                    if ($exists) {
                                        $fail('این محصول قبلاً در این انبار تعریف شده است.');
                                    }
                                };
                            })
                            ->columnSpanFull()
                            ->disabledOn('edit'),
                    ]),

                Section::make('وضعیت موجودی')
                    ->schema([
                        TextInput::make('quantity')
                            ->label('موجودی اولیه')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->disabledOn('edit')
                            ->helperText(fn($operation) => $operation === 'edit' ? 'برای تغییر موجودی از دکمه "اصلاح موجودی" در لیست استفاده کنید.' : null),

                        TextInput::make('reserved_quantity')
                            ->label('رزرو شده (در سفارشات)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->hintIcon('heroicon-m-question-mark-circle', 'این مقدار خودکار توسط سفارشات باز تنظیم می‌شود.'),
                    ])
                    ->columns(),

                Section::make('موقعیت و هشدار')
                    ->schema([
                        TextInput::make('shelf_location')
                            ->label('آدرس قفسه / ردیف')
                            ->placeholder('مثلاً: A-12-3')
                            ->maxLength(255),

                        TextInput::make('low_stock_threshold')
                            ->label('حد هشدار کمبود')
                            ->hintIcon('heroicon-m-question-mark-circle', 'اگر موجودی کمتر از این شود، هشدار می‌دهد.')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('مثلاً: 5'),
                    ])
                    ->columns(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('productVariant.sku')
            ->modifyQueryUsing(fn($query) => $query->with('productVariant.product'))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('productVariant.product.name')
                    ->label('نام محصول')
                    ->searchable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->fontFamily('mono')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('productVariant.attributes')
                    ->label('تنوع')
                    ->badge()
                    ->formatStateUsing(fn($state) => collect($state)->values()->join(' - '))
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('موجودی')
                    ->sortable()
                    ->size(TextSize::Large)
                    ->weight('bold')
                    ->color(fn($state, $record) => $state <= $record->low_stock_threshold ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('shelf_location')
                    ->label('قفسه')
                    ->icon('heroicon-m-map-pin')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('reserved_quantity')
                    ->label('رزرو شده')
                    ->numeric()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                Filter::make('low_stock')
                    ->label('کمبود موجودی (Low Stock)')
                    ->query(fn(Builder $query) => $query->whereColumn('quantity', '<=', 'low_stock_threshold'))
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions(ActionGroup::make([
                Action::make('edit_layout')
                    ->label('تنظیمات چیدمان')
                    ->icon('heroicon-m-squares-2x2')
                    ->fillForm(fn (Inventory $record): array => [
                        'shelf_location' => $record->shelf_location,
                        'low_stock_threshold' => $record->low_stock_threshold,
                    ])
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('shelf_location')
                                    ->label('آدرس قفسه / ردیف')
                                    ->placeholder('مثلاً: A-12-3')
                                    ->maxLength(255),

                                TextInput::make('low_stock_threshold')
                                    ->label('حد هشدار کمبود')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'اگر موجودی کمتر از این شود، هشدار می‌دهد.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('مثلاً: 5'),
                            ])
                            ->columns()
                            ->columnSpanFull(),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $record->update(Arr::only($data, ['shelf_location', 'low_stock_threshold']));

                        Notification::make()->success()->title('تنظیمات چیدمان ذخیره شد')->send();
                    }),

                Action::make('adjust_stock')
                    ->label('اصلاح موجودی')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->color('warning')
                    ->schema([
                        TextInput::make('new_quantity')
                            ->label('موجودی شمارش شده')
                            ->numeric()
                            ->required(),
                        Textarea::make('reason')
                            ->label('دلیل اصلاح')
                            ->required()
                            ->placeholder('مثلاً: اشتباه در شمارش قبلی / انبارگردانی'),
                    ])
                    ->action(function (Inventory $record, array $data) {
                        $diff = $data['new_quantity'] - $record->quantity;

                        if ($diff == 0) return;

                        DB::transaction(function () use ($record, $data, $diff) {
                            $record->transactions()->create([
                                'type' => InventoryTransactionType::Correction,
                                'quantity' => abs($diff),
                                'quantity_before' => $record->quantity,
                                'quantity_after' => $data['new_quantity'],
                                'user_id' => auth()->id(),
                                'reason' => $data['reason'],
                            ]);

                            $record->update(['quantity' => $data['new_quantity']]);
                        });

                        Notification::make()->success()->title('موجودی اصلاح شد')->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

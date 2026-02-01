<?php

namespace App\Filament\Resources\InventoryTransactions;

use App\Enums\InventoryTransactionType;
use App\Filament\Resources\InventoryTransactions\Pages\ManageInventoryTransactions;
use App\Models\InventoryTransaction;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'type';

    protected static ?string $navigationLabel = 'گزارش گردش کالا';

    protected static ?string $pluralModelLabel = 'گزارش گردش کالا';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with([
                'inventory.productVariant.product',
                'inventory.warehouse',
                'user'
            ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('inventory.productVariant.sku')
                    ->label('کالا')
                    ->description(fn($record) => $record->inventory->productVariant->product->name ?? '-')
                    ->searchable()
                    ->fontFamily('mono')
                    ->toggleable(),

                TextColumn::make('inventory.warehouse.name')
                    ->label('انبار')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('نوع عملیات')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('تعداد')
                    ->numeric()
                    ->formatStateUsing(function ($state, $record) {
                        $sign = ($record->quantity_after > $record->quantity_before) ? '+' : '-';
                        return $sign . ' ' . number_format($state);
                    })
                    ->color(fn($record) => ($record->quantity_after > $record->quantity_before) ? 'success' : 'danger')
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('کاربر')
                    ->placeholder('سیستم (خودکار)')
                    ->toggleable(),

                TextColumn::make('reason')
                    ->label('بابت / دلیل')
                    ->limit(30)
                    ->tooltip(fn($state) => $state)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('زمان')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => verta($state)->format('j F Y - H:i'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('warehouse')
                    ->label('انبار')
                    ->relationship('inventory.warehouse', 'name'),

                SelectFilter::make('type')
                    ->label('نوع عملیات')
                    ->options(InventoryTransactionType::class)
                    ->multiple(),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label('از تاریخ'), // todo: change to jalali
                        DatePicker::make('created_until')->label('تا تاریخ'), // todo: change to jalali
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryTransactions::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\ProductRelationType;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RelatedProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'productRelations';

    protected static ?string $title = 'محصولات مرتبط (Cross-sell / Upsell)';

    protected static ?string $modelLabel = 'محصول مرتبط';

    protected static ?string $pluralLabel = 'محصولات مرتبط';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('related_product_id')
                    ->label('محصول پیشنهادی')
                    ->relationship('relatedProduct', 'name', function (Builder $query, RelationManager $livewire) {
                        return $query->where('id', '!=', $livewire->getOwnerRecord()->id);
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull()
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} (SKU: {$record->sku})"),

                Select::make('type')
                    ->label('نوع پیشنهاد')
                    ->options(ProductRelationType::class)
                    ->required()
                    ->native(false),

                TextInput::make('position')
                    ->label('ترتیب نمایش')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->defaultSort('position')
            ->reorderable('position')
            ->modifyQueryUsing(fn($query) => $query->with('relatedProduct'))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('relatedProduct.thumbnail')
                    ->label('تصویر')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('relatedProduct.title')
                    ->label('نام محصول')
                    ->searchable()
                    ->weight('bold')
                    ->url(fn($record) => ProductResource::getUrl('edit', ['record' => $record->related_product_id]))
                    ->toggleable(),

                TextColumn::make('relatedProduct.sku')
                    ->label('SKU')
                    ->color('gray')
                    ->fontFamily('mono')
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('نوع رابطه')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('relatedProduct.price')
                    ->label('قیمت')
                    ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                    ->toggleable(),

                TextInputColumn::make('position')
                    ->label('ترتیب')
                    ->sortable()
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
                SelectFilter::make('type')
                    ->label('نوع رابطه')
                    ->options(ProductRelationType::class)
                    ->multiple(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

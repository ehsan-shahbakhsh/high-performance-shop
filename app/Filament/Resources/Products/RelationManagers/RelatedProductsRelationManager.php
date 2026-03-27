<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\ProductRelationType;
use App\Filament\Components\ShopTable;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\{BulkActionGroup, CreateAction, DeleteAction, DeleteBulkAction, EditAction};
use Filament\Forms\Components\{Select, TextInput};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\{ImageColumn, TextColumn};
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
                    ->columnSpanFull(),

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
            ->modifyQueryUsing(fn($query) => $query->with('relatedProduct.media'))
            ->columns([
                ShopTable::id(),

                ImageColumn::make('relatedProduct.thumbnail_url')
                    ->label('تصویر')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('relatedProduct.name')
                    ->label('نام محصول')
                    ->searchable()
                    ->weight('bold')
                    ->url(fn($record) => ProductResource::getUrl('edit', ['record' => $record->related_product_id]))
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('نوع رابطه')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('relatedProduct.min_price')
                    ->label('قیمت پایه')
                    ->formatStateUsing(fn($state) => number_format($state) . ' تومان')
                    ->toggleable(),

                ShopTable::position(),

                ShopTable::createdAt(),
                ShopTable::updatedAt(),
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

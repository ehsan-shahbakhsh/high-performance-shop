<?php

namespace App\Filament\Resources\Variants\RelationManagers;

use App\Enums\ProductBundleItemModifierType;
use App\Enums\ProductType;
use App\Filament\Components\ShopForm;
use App\Filament\Components\ShopTable;
use App\Models\ProductVariant;
use Filament\Actions\{CreateAction, DeleteAction, EditAction};
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class BundleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'bundleItems';

    protected static ?string $title = 'آیتم‌های باندل';

    protected static ?string $modelLabel = 'آیتم باندل';

    protected static ?string $pluralLabel = 'آیتم‌های باندل';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->product->type === ProductType::Bundle;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('child_variant_id')
                    ->label('تنوع محصول')
                    ->getSearchResultsUsing(function (string $search) {
                        return ProductVariant::query()
                            ->select(['id', 'sku', 'product_id'])
                            ->whereKeyNot($this->ownerRecord->id)
                            ->with('product:id,name')
                            ->where(static function ($query) use ($search) {
                                $query->where('sku', 'like', "%$search%")
                                    ->orWhereRelation('product', 'name', 'like', "%$search%");
                            })
                            ->limit(20)
                            ->get()
                            ->mapWithKeys(static fn($v) => [$v->id => "{$v->product->name} ({$v->sku})"]);
                    })
                    ->getOptionLabelUsing(static function ($value) {
                        return ProductVariant::query()
                            ->select(['id', 'product_id'])
                            ->with('product:id,name')
                            ->find($value)?->product->name;
                    })
                    ->searchable()
                    ->unique(
                        'product_bundle_items',
                        'child_variant_id',
                        ignoreRecord: true,
                        modifyRuleUsing: fn(Unique $rule) => $rule->where('parent_variant_id', $this->ownerRecord->id),
                    )
                    ->required(),

                TextInput::make('quantity')
                    ->label('تعداد')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                Select::make('modifier_type')
                    ->label('نوع تغییر قیمت')
                    ->options(ProductBundleItemModifierType::class)
                    ->default(ProductBundleItemModifierType::None)
                    ->required()
                    ->live(),

                TextInput::make('price_modifier')
                    ->label(static function (Get $get) {
                        return match ($get('modifier_type')) {
                            ProductBundleItemModifierType::Percentage => 'میزان درصد تخفیف',
                            ProductBundleItemModifierType::FixedDiscount => 'مبلغ تخفیف',
                            ProductBundleItemModifierType::FixedPrice => 'قیمت قطعی و جایگزین',
                            default => 'مقدار تغییر قیمت',
                        };
                    })
                    ->required()
                    ->mask(RawJs::make('$money($input)'))
                    ->prefix(static function (Get $get) {
                        if ($get('modifier_type') === ProductBundleItemModifierType::Percentage) {
                            return '٪';
                        }

                        return 'تومان';
                    })
                    ->minValue(0)
                    ->maxValue(static function (Get $get) {
                        if ($get('modifier_type') === ProductBundleItemModifierType::Percentage) {
                            return 100;
                        }

                        return null;
                    })
                    ->extraAttributes(['dir' => 'ltr'])
                    ->mutateDehydratedStateUsing(static function ($state) {
                        if (blank($state)) return null;

                        return str_replace(',', '', $state);
                    })
                    ->mutateStateForValidationUsing(static function ($state) {
                        if (blank($state)) return null;

                        return str_replace(',', '', $state);
                    })
                    ->inputMode('numeric')
                    ->rule('integer')
                    ->visible(static fn(Get $get) => $get('modifier_type') && $get('modifier_type') !== ProductBundleItemModifierType::None),

                TextInput::make('position')
                    ->label('ترتیب')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->modifyQueryUsing(static fn($query) => $query->with('childVariant.product:id,name'))
            ->columns([
                ShopTable::id(),

                TextColumn::make('childVariant.product.name')
                    ->searchable()
                    ->formatStateUsing(static function ($state, Model $record) {
                        return "$state ({$record->childVariant->sku})";
                    })
                    ->label('تنوع محصول')
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('تعداد')
                    ->badge()
                    ->formatStateUsing(static fn($state) => "$state عدد")
                    ->toggleable(),

                TextColumn::make('modifier_type')
                    ->label('نوع تغییر قیمت')
                    ->toggleable(),

                TextColumn::make('price_modifier')
                    ->label('مقدار')
                    ->formatStateUsing(static function ($state, $record) {
                        return match ($record->modifier_type) {
                            ProductBundleItemModifierType::Percentage =>
                            "{$state}٪",
                            ProductBundleItemModifierType::FixedDiscount, ProductBundleItemModifierType::FixedPrice =>
                                number_format($state) . ' تومان',
                            default => '—',
                        };
                    })
                    ->toggleable(),

                ShopTable::position(),

                ShopTable::createdAt(),
                ShopTable::updatedAt(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}

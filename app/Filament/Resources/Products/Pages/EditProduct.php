<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\AttributeType;
use App\Enums\ProductType;
use App\Filament\Components\ShopForm;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Product;
use App\Services\Catalog\SkuGenerator;
use App\Services\Catalog\VariantGenerator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $product = $this->getRecord();
        $product->loadMissing([
            'attributes' => fn($query) => $query->orderBy('position')->with('attribute'),
            'attributeValues',
            'attributeMultiValues',
        ]);

        foreach ($product->attributes as $attribute) {
            $attributeData = [
                'attribute_type' => $attribute->attribute->type,
                'attribute_id' => $attribute->attribute_id,
            ];

            if ($attribute->attribute->type === AttributeType::MultiSelect) {
                $optionIds = $product->attributeMultiValues
                    ->where('attribute_id', $attribute->attribute_id)
                    ->pluck('attribute_option_id')
                    ->toArray();
                $attributeData['attribute_option_ids'] = $optionIds;
            } else {
                $value = $product->attributeValues->firstWhere('attribute_id', $attribute->attribute_id);

                $attributeData = array_merge($attributeData, [
                    'attribute_option_id' => $value->attribute_option_id,
                    'value_text' => $value->value_text,
                    'value_string' => $value->value_string,
                    'value_number' => $value->value_number,
                    'value_boolean' => $value->value_boolean,
                    'value_date' => $value->value_date,
                ]);
            }

            $data['attributes'][] = $attributeData;
        }

        if ($product->type === ProductType::Simple) {
            $defaultVariant = $product->defaultVariant;

            $data['price'] = $defaultVariant->price;
            $data['sale_price'] = $defaultVariant->sale_price;
            $data['sku'] = $defaultVariant->sku;
            $data['sale_start'] = $defaultVariant->sale_start;
            $data['sale_end'] = $defaultVariant->sale_end;
            $data['weight'] = $defaultVariant->weight;
            $data['length'] = $defaultVariant->length;
            $data['width'] = $defaultVariant->width;
            $data['height'] = $defaultVariant->height;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $variantColumns = [
            'price', 'sale_price', 'sku', 'sale_start', 'sale_end', 'weight', 'length', 'width', 'height'
        ];

        $record->update(Arr::except($data, $variantColumns + ['attributes']));

        if ($record instanceof Product) {
            $this->deleteAttributes($record);
            $this->createAttributes($record, $data['attributes']);
        }

        if ($data['type'] === ProductType::Simple) {
            $defaultVariant = $record->defaultVariant;

            if ($record instanceof Product && is_null($data['sku']))
                $data['sku'] = resolve(SkuGenerator::class)
                    ->generate($record, attributes: $record->getAttributeValuesForSku(), ignore: $defaultVariant->id);

            $defaultVariant->update(Arr::only($data, $variantColumns));

            $this->form->fill($data);
        }

        return $record;
    }

    private function deleteAttributes(Product $product): void
    {
        $product->attributes()->delete();
        $product->attributeValues()->delete();
        $product->attributeMultiValues()->delete();
    }

    private function createAttributes(Product $product, array $attributes): void
    {
        $attributesData = [];
        $valuesData = [];
        $multiData = [];

        foreach ($attributes as $i => $attr) {
            $attributesData[] = [
                'position' => $i + 1,
                'attribute_id' => $attr['attribute_id'],
            ];

            $attributeId = $attr['attribute_id'];
            $optionIds = $attr['attribute_option_ids'] ?? null;

            if (!empty($optionIds)) {
                foreach ($optionIds as $optionId) {
                    $multiData[] = [
                        'attribute_id' => $attributeId,
                        'attribute_option_id' => $optionId,
                    ];
                }
                continue;
            }

            $valuesData[] = [
                'attribute_id' => $attributeId,
                'attribute_option_id' => $attr['attribute_option_id'] ?? null,
                'value_text' => $attr['value_text'] ?? null,
                'value_string' => $attr['value_string'] ?? null,
                'value_number' => $attr['value_number'] ?? null,
                'value_boolean' => $attr['value_boolean'] ?? null,
                'value_date' => $attr['value_date'] ?? null,
            ];
        }

        $product->attributes()->createMany($attributesData);
        $product->attributeValues()->createMany($valuesData);
        $product->attributeMultiValues()->createMany($multiData);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateVariants')
                ->label('تولید واریانت‌ها (تنوع محصول)')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->color('primary')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            ShopForm::price()
                                ->columnSpan(1),

                            Placeholder::make('variants_count')
                                ->label('تعداد واریانت‌های قابل ساخت')
                                ->content(static function (Get $get) {
                                    $attributes = $get('attributes') ?? [];

                                    if (blank($attributes)) return '0 واریانت ساخته خواهد شد';

                                    $count = 0;

                                    foreach ($attributes as $attr) {
                                        $options = $attr['attribute_option_ids'] ?? [];

                                        if (filled($options) && $count == 0) $count = 1;

                                        $count *= max(count($options), 1);
                                    }

                                    return $count . ' واریانت ساخته خواهد شد';
                                })
                                ->columnSpan(1),
                        ]),

                    Section::make('ویژگی‌ها')
                        ->schema([
                            Repeater::make('attributes')
                                ->hiddenLabel()
                                ->addActionLabel('افزودن ویژگی')
                                ->schema([
                                    Select::make('attribute_id')
                                        ->label('ویژگی')
                                        ->options(static function () {
                                            return Attribute::query()
                                                ->where('is_variant', true)
                                                ->where('type', AttributeType::Select)
                                                ->pluck('name', 'id');
                                        })
                                        ->reactive()
                                        ->afterStateUpdated(static function ($state, Set $set) {
                                            $set('attribute_option_ids', []);
                                        })
                                        ->required()
                                        ->distinct()
                                        ->validationMessages([
                                            'distinct' => 'امکان ثبت ویژگی تکراری وجود ندارد.',
                                        ]),

                                    Select::make('attribute_option_ids')
                                        ->label('مقادیر')
                                        ->multiple()
                                        ->options(static function (Get $get) {
                                            $attributeId = $get('attribute_id');
                                            if (!$attributeId) return [];

                                            return AttributeOption::query()
                                                ->where('attribute_id', $attributeId)
                                                ->pluck('label', 'id');
                                        })
                                        ->required()
                                        ->lazy()
                                        ->visible(static fn(Get $get) => $get('attribute_id')),
                                ])
                                ->columns(2)
                                ->default([])
                                ->live(),
                        ])
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, Action $action) {
                    try {
                        $newVariantsCount = resolve(VariantGenerator::class)->generate(
                            $this->record,
                            $data['attributes'],
                            $data['price'],
                        );

                        $action->success();
                        $action->successRedirectUrl($this->getResourceUrl('edit', ['record' => $action->getRecord()]));
                        $action->successNotificationTitle("{$newVariantsCount} واریانت جدید با موفقیت ساخته شد.");
                    } catch (\Throwable $e) {
                        report($e);

                        $action->failureNotificationTitle('در ایجاد واریانت‌های محصول مشکلی پیش آمده است');
                        $action->failure();
                        $action->halt();
                    }
                }),

            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

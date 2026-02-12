<?php

namespace App\Filament\Resources\ShippingCarriers\Schemas;

use App\Filament\Components\ShopForm;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShippingCarrierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات پایه')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('نام شرکت')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label('کد سیستمی (Driver)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('مثلاً: post, alopeyk, tipax'),

                        TextInput::make('tracking_url_template')
                            ->label('الگوی لینک رهگیری')
                            ->columnSpanFull()
                            ->placeholder('https://tracking.post.ir/?id={tracking_code}'),

                        FileUpload::make('logo_path')
                            ->label('لوگو')
                            ->image()
                            ->directory('carriers')
                            ->avatar()
                            ->imageEditor(),
                    ]),

                Section::make('تنظیمات پیشرفته')
                    ->schema([
                        KeyValue::make('settings')
                            ->label('تنظیمات درایور (JSON)')
                            ->keyLabel('کلید (مثل API Key)')
                            ->valueLabel('مقدار')
                            ->reorderable(),

                        ShopForm::status('is_active', 'فعال است'),
                    ]),
            ]);
    }
}

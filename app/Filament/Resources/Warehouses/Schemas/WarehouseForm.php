<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Filament\Components\ShopForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات انبار')
                    ->schema([
                        TextInput::make('name')
                            ->label('نام انبار')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label('کد انبار')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        TextInput::make('city')
                            ->label('شهر')
                            ->maxLength(100),

                        TextInput::make('address')
                            ->label('آدرس دقیق')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('تنظیمات')
                    ->schema([
                        ShopForm::status('is_active', 'انبار فعال است'),

                        Toggle::make('is_default')
                            ->label('انبار پیش‌فرض')
                            ->hintIcon('heroicon-m-question-mark-circle', 'با فعال کردن این گزینه، این انبار برای سفارشات جدید در اولویت قرار می‌گیرد.')
                            // لاجیک: اگر این روشن شد، بقیه باید خاموش شوند (توی Observer بهتر هندل میشه ولی اینجا هم میشه)
                            ->default(false),
                    ])->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label('نام'),

                                TextInput::make('website')
                                    ->label('وب‌سایت')
                                    ->rule('url')
                                    ->prefix('https://')
                                    ->suffixIcon('heroicon-m-globe-alt')
                                    ->extraAttributes(['dir' => 'ltr'])
                                    ->mutateStateForValidationUsing(function ($state) {
                                        return str_starts_with($state, 'https://') ? $state : 'https://' . $state;
                                    }),

                                MarkdownEditor::make('description')
                                    ->label('توضیحات')
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('وضعیت')
                            ->schema([
                                Toggle::make('is_active')
                                    ->required()
                                    ->label('فعال')
                                    ->default(true),

                                Toggle::make('is_featured')
                                    ->required()
                                    ->label('ویژه'),
                            ]),

                        Section::make('تصاویر')
                            ->schema([
                                FileUpload::make('logo')
                                    ->label('لوگو')
                                    ->image()
                                    ->directory('brands/logos'),

                                FileUpload::make('cover')
                                    ->label('کاور')
                                    ->image()
                                    ->directory('brands/covers'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}

<?php

namespace App\Filament\Resources\Provinces\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProvinceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('name_en'),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('tel_prefix')
                    ->tel(),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('position')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}

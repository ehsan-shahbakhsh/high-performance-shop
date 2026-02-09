<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('province_id')
                    ->relationship('province', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('name_en'),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('has_shipping')
                    ->required(),
            ]);
    }
}

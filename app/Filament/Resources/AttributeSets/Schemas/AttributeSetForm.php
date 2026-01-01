<?php

namespace App\Filament\Resources\AttributeSets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttributeSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->unique()
                    ->label('نام ست')
                    ->required()
                    ->placeholder('مثال: موبایل، کامپیوتر')
                    ->maxLength(255),
            ]);
    }
}

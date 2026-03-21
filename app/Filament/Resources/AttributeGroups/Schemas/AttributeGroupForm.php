<?php

namespace App\Filament\Resources\AttributeGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttributeGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام گروه')
                    ->placeholder('مثال: مشخصات فنی، ابعاد و وزن، ویژگی‌های ظاهری')
                    ->required()
                    ->maxLength(200)
                    ->unique(ignoreRecord: true),
            ]);
    }
}

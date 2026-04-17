<?php

namespace App\Filament\Resources\Discounts\RelationManagers;

use App\Filament\Components\ShopForm;
use App\Filament\Resources\Coupons\CouponResource;
use Filament\Actions\{Action, CreateAction, EditAction};
use Filament\Forms\Components\{DateTimePicker, TextInput};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\{Grid, Section, Utilities\Set};
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class CouponsRelationManager extends RelationManager
{
    protected static string $relationship = 'coupons';

    protected static ?string $relatedResource = CouponResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات اصلی کد تخفیف')
                    ->schema([
                        TextInput::make('code')
                            ->label('کد تخفیف')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[a-zA-Z0-9_-]*$/')
                            ->helperText('فقط حروف انگلیسی، اعداد و خط تیره مجاز است.')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->suffixAction(
                                Action::make('generate_code')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->tooltip('تولید کد تصادفی')
                                    ->action(static function (Set $set) {
                                        $set('code', 'OFF-' . strtoupper(Str::random(6)));
                                    }),
                            )
                            ->columnSpanFull(),
                    ]),

                Section::make('محدودیت‌ها و آمار مصرف')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('usage_limit')
                                ->label('سقف استفاده کل')
                                ->numeric()
                                ->minValue(1)
                                ->placeholder('بی‌نهایت'),

                            TextInput::make('user_usage_limit')
                                ->label('سقف استفاده هر کاربر')
                                ->numeric()
                                ->minValue(1)
                                ->placeholder('نامحدود'),

                            TextInput::make('used')
                                ->label('دفعات استفاده شده')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated(false)
                                ->hintIcon(Heroicon::QuestionMarkCircle, 'این فیلد به صورت خودکار توسط سیستم آپدیت می‌شود.'),
                        ]),
                    ]),

                Section::make('وضعیت و اعتبار')
                    ->schema([
                        Grid::make(2)->schema([
                            DateTimePicker::make('expires_at')
                                ->label('تاریخ انقضا')
                                ->jalali()
                                ->native(false)
                                ->seconds(false)
                                ->displayFormat('Y/m/d H:i')
                                ->placeholder('بدون انقضا'),

                            ShopForm::status('is_active', 'وضعیت فعال بودن')
                                ->inline(false),
                        ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([CreateAction::make()->modal()])
            ->recordActions([EditAction::make()->modal()]);
    }
}

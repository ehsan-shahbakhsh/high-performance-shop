<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Components\ShopForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'فایل‌ها';

    protected static ?string $modelLabel = 'فایل';

    protected static ?string $pluralLabel = 'فایل‌ها';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('اطلاعات فایل و آپلود')
                    ->description('فایل‌ها در فضای محافظت‌شده ذخیره می‌شوند و لینک مستقیم ندارند.')
                    ->schema([
                        TextInput::make('display_name')
                            ->label('عنوان نمایشی')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Select::make('product_variant_id')
                            ->label('اختصاص به تنوع خاص (اختیاری)')
                            ->relationship(
                                name: 'productVariant',
                                titleAttribute: 'sku',
                                modifyQueryUsing: fn($query, RelationManager $livewire) => $query->where('product_id', $livewire->getOwnerRecord()->id)
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('مشترک برای همه تنوع‌ها')
                            ->columnSpan(1),

                        FileUpload::make('storage_path')
                            ->label('فایل دانلودی')
                            ->required()
                            ->disk('local')
                            ->directory('product-downloads')
                            ->visibility('private')
                            ->storeFileNamesIn('filename')
                            ->acceptedFileTypes(['application/pdf', 'application/zip', 'audio/*', 'video/*'])
                            ->columnSpanFull(),
                    ])
                    ->columns(),

                Section::make('محدودیت‌های دسترسی')
                    ->schema([
                        TextInput::make('download_limit')
                            ->label('سقف دانلود')
                            ->hintIcon('heroicon-m-question-mark-circle', 'تعداد دفعاتی که خریدار می‌تواند فایل را دانلود کند (خالی = نامحدود)')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('بار'),

                        TextInput::make('expiry_days')
                            ->label('مهلت استفاده')
                            ->hintIcon('heroicon-m-question-mark-circle', 'تعداد روزهایی که لینک پس از خرید فعال است (خالی = نامحدود)')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('روز'),

                        ShopForm::status('is_active', 'وضعیت فعال بودن'),
                    ])
                    ->columns(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات عمومی')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        TextEntry::make('display_name')
                            ->label('عنوان نمایشی')
                            ->weight('bold')
                            ->size(TextSize::Large),

                        TextEntry::make('productVariant.sku')
                            ->label('تنوع مرتبط')
                            ->placeholder('مشترک برای همه تنوع‌ها')
                            ->badge()
                            ->color('warning'),

                        IconEntry::make('is_active')
                            ->label('وضعیت انتشار')
                            ->boolean(),
                    ])->columns(3),

                Section::make('قوانین دانلود')
                    ->icon('heroicon-m-lock-closed')
                    ->schema([
                        TextEntry::make('download_limit')
                            ->label('سقف دانلود مجاز')
                            ->placeholder('نامحدود')
                            ->suffix(' بار'),

                        TextEntry::make('expiry_days')
                            ->label('مدت اعتبار لینک')
                            ->placeholder('نامحدود')
                            ->suffix(' روز پس از خرید'),
                    ])->columns(2),

                Section::make('جزئیات فنی فایل')
                    ->icon('heroicon-m-cpu-chip')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('filename')
                            ->label('نام فایل در سیستم')
                            ->fontFamily('mono')
                            ->copyable(),

                        TextEntry::make('size_formatted')
                            ->label('حجم فایل'),

                        TextEntry::make('mime_type')
                            ->label('فرمت (MIME)')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('disk')
                            ->label('دیسک ذخیره‌سازی'),

                        TextEntry::make('storage_path')
                            ->label('مسیر کامل')
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاریخ ایجاد')
                            ->dateTime()
                            ->formatStateUsing(fn($state) => verta($state)->format('j F Y - H:i'))
                            ->color('gray'),

                        TextEntry::make('updated_at')
                            ->label('آخرین بروزرسانی')
                            ->dateTime()
                            ->formatStateUsing(fn($state) => verta($state)->format('j F Y - H:i'))
                            ->color('gray'),
                    ])->columns(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->defaultSort('position')
            ->reorderable('position')
            ->modifyQueryUsing(fn($query) => $query->with('productVariant'))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('شناسه')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('productVariant.sku')
                    ->searchable()
                    ->label('تنوع محصول')
                    ->toggleable(),

                TextColumn::make('display_name')
                    ->searchable()
                    ->label('عنوان فایل')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('filename')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('size_formatted')
                    ->label('حجم')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('mime_type')
                    ->label('فرمت')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state) {
                        return str($state)->afterLast('/')->upper();
                    })
                    ->toggleable(),

                TextColumn::make('download_limit')
                    ->label('حد دانلود')
                    ->numeric()
                    ->sortable()
                    ->placeholder('نامحدود')
                    ->toggleable(),

                TextColumn::make('expiry_days')
                    ->label('مهلت (روز)')
                    ->numeric()
                    ->sortable()
                    ->suffix(' روز')
                    ->placeholder('نامحدود')
                    ->toggleable(),

                TextColumn::make('filename')
                    ->label('نام سیستمی')
                    ->searchable()
                    ->copyable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('وضعیت')
                    ->onColor('success')
                    ->offColor('danger')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ ایجاد')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('تاریخ آخرین بروزرسانی')
                    ->formatStateUsing(fn($state) => verta($state)->formatDatetime())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت انتشار')
                    ->trueLabel('فقط فعال‌ها')
                    ->falseLabel('فقط غیرفعال‌ها'),

                SelectFilter::make('mime_type')
                    ->label('نوع فایل')
                    ->options([
                        'application/pdf' => 'PDF',
                        'application/zip' => 'ZIP / Archive',
                        'video/' => 'Video',
                        'audio/' => 'Audio',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('mime_type', 'like', "%{$data['value']}%");
                        }
                    }),

                SelectFilter::make('product_variant_id')
                    ->label('مربوط به تنوع')
                    ->relationship(
                        name: 'productVariant',
                        titleAttribute: 'sku',
                        modifyQueryUsing: fn($query, $livewire) => $query->where('product_id', $livewire->getOwnerRecord()->id)
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $disk = 'local';
                        $data['disk'] = $disk;
                        $path = $data['storage_path'];

                        if (Storage::disk($disk)->exists($path)) {
                            $data['size'] = Storage::disk($disk)->size($path);
                            $data['mime_type'] = Storage::disk($disk)->mimeType($path);
                        }

                        if (empty($data['position'])) {
                            $data['position'] = 999;
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

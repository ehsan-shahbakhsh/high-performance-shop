<?php

namespace App\Filament\Resources\Variants\RelationManagers;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use App\Filament\Components\{ShopForm, ShopTable};
use Filament\Actions\{BulkActionGroup, CreateAction, DeleteBulkAction, EditAction, ViewAction};
use Filament\Forms\Components\{Hidden, SpatieMediaLibraryFileUpload, TextInput};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\{SelectFilter, TernaryFilter};
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\{Builder, Model};

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'فایل‌ها';

    protected static ?string $modelLabel = 'فایل';

    protected static ?string $pluralLabel = 'فایل‌ها';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->product->is_downloadable;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('product_id')->default($this->getOwnerRecord()->product_id),

                TextInput::make('display_name')
                    ->label('عنوان فایل')
                    ->required()
                    ->maxLength(255),

                TextInput::make('version')
                    ->label('نسخه')
                    ->regex('/^\d+(\.\d+){1,2}$/')
                    ->placeholder('مثال: 1.0.0')
                    ->default('1.0.0'),

                SpatieMediaLibraryFileUpload::make('file')
                    ->label('فایل')
                    ->collection('variant_file')
                    ->disk('local')
                    ->directory('variant-files')
                    ->maxSize(2048000)
                    ->acceptedFileTypes([
                        'application/zip',
                        'application/x-rar-compressed',
                        'application/x-7z-compressed',
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'audio/mpeg',
                        'video/mp4',
                    ])
                    ->downloadable()
                    ->openable()
                    ->required(),

                TextInput::make('download_limit')
                    ->numeric()
                    ->integer()
                    ->label('محدودیت دانلود')
                    ->helperText('برای دانلود نامحدود خالی بگذارید'),

                TextInput::make('expiry_days')
                    ->numeric()
                    ->label('مدت اعتبار (روز)'),

                TextInput::make('position')
                    ->numeric()
                    ->label('ترتیب نمایش')
                    ->integer()
                    ->default(0),

                ShopForm::status('is_active', 'وضعیت فعال بودن'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        $mediaFile = $schema->getRecord()->getFirstMedia('variant_file');

        return $schema
            ->components([
                Section::make('مشخصات عمومی')
                    ->icon(Heroicon::InformationCircle)
                    ->schema([
                        TextEntry::make('display_name')
                            ->label('عنوان نمایشی')
                            ->weight('bold')
                            ->size(TextSize::Large),

                        IconEntry::make('is_active')
                            ->label('وضعیت انتشار')
                            ->boolean(),
                    ])->columns(3),

                Section::make('قوانین دانلود')
                    ->icon(Heroicon::LockClosed)
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
                    ->icon(Heroicon::CpuChip)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('filename')
                            ->label('نام فایل در سیستم')
                            ->fontFamily('mono')
                            ->state($mediaFile?->name)
                            ->copyable(),

                        TextEntry::make('size')
                            ->label('حجم فایل')
                            ->state($mediaFile?->human_readable_size ?? 'N/A')
                            ->badge(),

                        TextEntry::make('mime_type')
                            ->label('فرمت (MIME)')
                            ->badge()
                            ->state($mediaFile?->mime_type)
                            ->color('gray'),
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
            ->modifyQueryUsing(fn($query) => $query->with('media'))
            ->columns([
                ShopTable::id(),

                TextColumn::make('display_name')
                    ->searchable()
                    ->label('عنوان فایل')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('size')
                    ->label('اندازه')
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        return $record->getFirstMedia('variant_file')?->human_readable_size ?? 'N/A';
                    })
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('version')
                    ->searchable()
                    ->label('ورژن')
                    ->toggleable(),

                TextColumn::make('download_limit')
                    ->label('محدودیت دانلود')
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

                ShopTable::position(),
                ShopTable::status(),

                ShopTable::createdAt(),
                ShopTable::updatedAt(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت انتشار')
                    ->trueLabel('فقط فعال‌ها')
                    ->falseLabel('فقط غیرفعال‌ها'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('افزودن فایل'),
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

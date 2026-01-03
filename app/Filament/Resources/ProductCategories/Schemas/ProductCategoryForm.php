<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use App\Filament\Components\ShopForm;
use App\Models\Icon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('اطلاعات عمومی')
                            ->description('اطلاعات پایه و نام‌گذاری دسته')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('name')
                                    ->label('عنوان دسته')
                                    ->required()
                                    ->maxLength(200),

                                Select::make('parent_id')
                                    ->label('دسته والد')
                                    ->relationship('parent', 'name', function (Builder $query, ?Model $record) {
                                        if ($record) {
                                            return $query->where('id', '!=', $record->id);
                                        }
                                        return $query;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('--- دسته اصلی (ریشه) ---')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'اگر این دسته زیرمجموعه دسته دیگری است، آن را انتخاب کنید.'),

                                MarkdownEditor::make('description')
                                    ->label('توضیحات')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('بهینه‌سازی موتورهای جستجو (SEO)')
                            ->description('تنظیمات متا تگ‌ها برای گوگل و سایر موتورها')
                            ->icon('heroicon-o-globe-alt')
                            ->collapsed()
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('عنوان سئو (Title)')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'پیشنهاد: حداکثر ۶۰ کاراکتر. اگر خالی باشد، از عنوان دسته استفاده می‌شود.')
                                    ->maxLength(60)
                                    ->placeholder(fn(Get $get) => $get('name'))
                                    ->columnSpanFull(),

                                Textarea::make('seo_description')
                                    ->label('توضیحات سئو (Meta Description)')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'پیشنهاد: حداکثر ۱۶۰ کاراکتر. خلاصه‌ای جذاب برای نمایش در نتایج گوگل.')
                                    ->maxLength(160)
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('تصویر شاخص')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('cover')
                                    ->collection('category_cover')
                                    ->hiddenLabel()
                                    ->image()
                                    ->imageEditor()
                                    ->columnSpanFull(),

                                ShopForm::iconPicker(),
                            ]),

                        Section::make('وضعیت نمایش')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                ShopForm::status('is_active', 'فعال', 'آیا در سایت نمایش داده شود؟'),

                                Toggle::make('is_featured')
                                    ->label('نمایش به عنوان ویژه')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'نمایش در بخش‌های برجسته سایت')
                                    ->onColor('warning'),

                                Toggle::make('include_in_menu')
                                    ->label('نمایش در منو')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'آیا در منوی اصلی سایت باشد؟')
                                    ->onColor('info'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}

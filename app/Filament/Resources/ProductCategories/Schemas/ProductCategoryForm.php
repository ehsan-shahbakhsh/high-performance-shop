<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use App\Models\Icon;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
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
                                    ->helperText('اگر این دسته زیرمجموعه دسته دیگری است، آن را انتخاب کنید.'),

                                MarkdownEditor::make('description')
                                    ->label('توضیحات')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('بهینه‌سازی موتورهای جستجو (SEO)')
                            ->description('تنظیمات متا تگ‌ها برای گوگل و سایر موتورها')
                            ->icon('heroicon-o-presentation-chart-line')
                            ->collapsed()
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('عنوان سئو (Title)')
                                    ->helperText('پیشنهاد: حداکثر ۶۰ کاراکتر. اگر خالی باشد، از عنوان دسته استفاده می‌شود.')
                                    ->maxLength(60)
                                    ->placeholder(fn(Get $get) => $get('name'))
                                    ->columnSpanFull(),

                                Textarea::make('seo_description')
                                    ->label('توضیحات سئو (Meta Description)')
                                    ->helperText('پیشنهاد: حداکثر ۱۶۰ کاراکتر. خلاصه‌ای جذاب برای نمایش در نتایج گوگل.')
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
                                FileUpload::make('cover')
                                    ->hiddenLabel()
                                    ->image()
                                    ->imageEditor()
                                    ->directory('categories/covers')
                                    ->columnSpanFull(),

                                Group::make()
                                    ->schema([
                                        Select::make('icon')
                                            ->label('آیکون')
                                            ->searchable()
                                            ->placeholder('نام آیکون را جستجو کنید (مثلا home)...')
                                            ->allowHtml()
                                            ->native(false)
                                            ->getSearchResultsUsing(function (string $search) {
                                                return Icon::query()
                                                    ->where('name', 'like', "%{$search}%")
                                                    ->orWhere('full_name', 'like', "%{$search}%")
                                                    ->limit(20)
                                                    ->get()
                                                    ->mapWithKeys(function ($icon) {
                                                        try {
                                                            $svgHtml = svg($icon->full_name)->toHtml();
                                                        } catch (\Exception) {
                                                            $svgHtml = '';
                                                        }

                                                        $svgHtml = str_replace(
                                                            '<svg',
                                                            '<svg style="width: 100% !important; height: 100% !important"',
                                                            $svgHtml
                                                        );

                                                        $html = <<<HTML
                                                            <div class="flex items-center gap-2">
                                                                <div style="width: 20px; height: 20px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                                                {$svgHtml}
                                                            </div>
                                                            <span style="font-size: 0.9rem;">{$icon->full_name}</span>
                                                            </div>
                                                        HTML;

                                                        return [$icon->full_name => $html];
                                                    });
                                            })
                                            ->getOptionLabelUsing(function ($value) {
                                                if ($value != strip_tags($value)) {
                                                    return $value;
                                                }

                                                try {
                                                    $svgHtml = svg($value)->toHtml();
                                                } catch (\Exception) {
                                                    $svgHtml = '';
                                                }

                                                $svgHtml = str_replace(
                                                    '<svg',
                                                    '<svg style="width: 100% !important; height: 100% !important"',
                                                    $svgHtml
                                                );

                                                return <<<HTML
                                                    <div class="flex items-center gap-2">
                                                        <div style="width: 20px; height: 20px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                                        {$svgHtml}
                                                    </div>
                                                        <span style="font-size: 0.9rem;">{$value}</span>
                                                        </div>
                                                    HTML;
                                            }),
                                    ]),
                            ]),

                        Section::make('وضعیت نمایش')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('فعال')
                                    ->helperText('آیا در سایت نمایش داده شود؟')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),

                                Toggle::make('is_featured')
                                    ->label('نمایش به عنوان ویژه')
                                    ->helperText('نمایش در بخش‌های برجسته سایت')
                                    ->onColor('warning'),

                                Toggle::make('include_in_menu')
                                    ->label('نمایش در منو')
                                    ->helperText('آیا در منوی اصلی سایت باشد؟')
                                    ->onColor('info'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}

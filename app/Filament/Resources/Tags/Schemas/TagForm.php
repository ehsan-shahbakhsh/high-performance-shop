<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Enums\TagType;
use App\Models\Icon;
use App\Models\Tag;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('اطلاعات پایه')
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('نام برچسب')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                                                if (($get('slug') ?? '') !== SlugService::createSlug(Tag::class, 'slug', $old ?? '')) {
                                                    return;
                                                }
                                                $set('slug', SlugService::createSlug(Tag::class, 'slug', $state ?? ''));
                                            }),

                                        TextInput::make('slug')
                                            ->label('نامک (Slug)')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->dehydrated(),
                                    ]),

                                RichEditor::make('description')
                                    ->label('توضیحات (نمایش در صفحه آرشیو)')
                                    ->toolbarButtons([
                                        'bold', 'italic', 'link', 'bulletList', 'orderedList', 'h2', 'h3'
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Section::make('بهینه‌سازی موتورهای جستجو (SEO)')
                            ->description('تنظیمات متا تگ‌ها برای گوگل و سایر موتورها')
                            ->icon('heroicon-o-globe-alt')
                            ->collapsed()
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('عنوان سئو (Title)')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'پیشنهاد: حداکثر ۶۰ کاراکتر. اگر خالی باشد، از نام برچسب استفاده می‌شود.')
                                    ->maxLength(60)
                                    ->placeholder(fn(Get $get) => $get('name'))
                                    ->columnSpanFull(),

                                Textarea::make('seo_description')
                                    ->label('توضیحات سئو (Meta Description)')
                                    ->hintIcon('heroicon-m-question-mark-circle', 'پیشنهاد: حداکثر ۱۶۰ کاراکتر. خلاصه‌ای جذاب برای نمایش در نتایج گوگل.')
                                    ->maxLength(160)
                                    ->rows(3)
                                    ->columnSpanFull(),

                                TextInput::make('canonical_url')
                                    ->label('لینک کانونیکال')
                                    ->rule('url')
                                    ->prefix('https://')
                                    ->extraAttributes(['dir' => 'ltr'])
                                    ->maxLength(255)
                                    ->mutateStateForValidationUsing(function ($state) {
                                        if (blank($state)) return $state;

                                        if (Str::isUrl($state)) {
                                            return $state;
                                        }

                                        return 'https://' . $state;
                                    }),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('ویژگی‌های ظاهری')
                            ->schema([
                                Select::make('type')
                                    ->label('نوع برچسب')
                                    ->options(TagType::class)
                                    ->default(TagType::General)
                                    ->required()
                                    ->searchable(),

                                ColorPicker::make('color')
                                    ->label('رنگ نشان (Badge)'),

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
                            ->schema([
                                Toggle::make('is_visible')
                                    ->label('نمایش در سایت')
                                    ->onColor('success')
                                    ->default(true),

                                Toggle::make('is_featured')
                                    ->label('برچسب ویژه')
                                    ->helperText('نمایش در لیست‌های منتخب.')
                                    ->default(false),

                                TextInput::make('position')
                                    ->label('ترتیب نمایش')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}

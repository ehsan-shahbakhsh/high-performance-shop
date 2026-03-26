<?php

namespace App\Filament\Components;

use App\Models\Icon;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Filament\Actions\Action;
use Filament\Forms\Components\{Select, TextInput, Toggle};
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Support\{RawJs, Icons\Heroicon};

final class ShopForm
{
    public static function price(string $name = 'price', string $label = 'قیمت', bool $isRequired = true): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->integer()
            ->required($isRequired)
            ->mask(RawJs::make('$money($input)'))
            ->prefix('تومان')
            ->maxValue(999999999999)
            ->extraAttributes(['dir' => 'ltr'])
            ->mutateStateForValidationUsing(fn($state) => str_replace(',', '', $state ?? ''));
    }

    public static function status(string $name, string $label, ?string $hint = null): Toggle
    {
        $field = Toggle::make($name)
            ->label($label)
            ->default(true)
            ->required()
            ->onColor('success')
            ->offColor('danger')
            ->onIcon(Heroicon::Check)
            ->offIcon(Heroicon::XMark);

        if ($hint !== null) {
            $field->hintIcon(Heroicon::QuestionMarkCircle, $hint);
        }

        return $field;
    }

    public static function iconPicker(string $name = 'icon', string $label = 'آیکون'): Select
    {
        return Select::make($name)
            ->label($label)
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
            });
    }

    public static function slug(string $model, string $name = 'slug', string $label = 'نامک (Slug)', ?string $generateFrom = null): TextInput
    {
        $field = TextInput::make($name)
            ->label($label)
            ->hintIcon(Heroicon::QuestionMarkCircle, 'نامک همان متنی است که در انتهای آدرس مرورگر نمایش داده می‌شود.')
            ->required()
            ->prefixIcon(Heroicon::Link)
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->live(onBlur: true)
            ->afterStateUpdated(fn(Set $set, ?string $state) => $set($name, SlugService::createSlug($model, $name, $state ?? '')))
            ->regex('/^[a-z0-9\-\_]+$/');

        if ($generateFrom) {
            $field->suffixAction(
                Action::make('generateSlug')
                    ->icon(Heroicon::ArrowPath)
                    ->tooltip('ساخت مجدد اسلاگ')
                    ->action(function (Get $get, Set $set) use ($generateFrom, $model, $name) {
                        $set(
                            $name,
                            SlugService::createSlug($model,
                                $name,
                                $get($generateFrom) ?? '',
                            )
                        );
                    })
            );
        }

        return $field;
    }
}

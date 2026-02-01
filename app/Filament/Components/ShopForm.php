<?php

namespace App\Filament\Components;

use App\Models\Icon;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

final class ShopForm
{
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
            $field->hintIcon('heroicon-m-question-mark-circle', $hint);
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

    public static function slug(string $model, string $name = 'slug', string $label = 'نامک (Slug)'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->hintIcon('heroicon-m-question-mark-circle', 'نامک همان متنی است که در انتهای آدرس مرورگر نمایش داده می‌شود.')
            ->required()
            ->prefixIcon('heroicon-m-link')
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->live(onBlur: true)
            ->afterStateUpdated(fn (Set $set, ?string $state) => $set($name, SlugService::createSlug($model, $name, $state ?? '')))
            ->regex('/^[a-z0-9\-\_]+$/');
    }
}

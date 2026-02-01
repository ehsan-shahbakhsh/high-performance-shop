<?php

namespace App\Http\Resources\Concerns;

trait DateFormatted
{
    public function toPersianDate($date): ?string
    {
        return $date ? verta($date)->format('Y/m/d H:i') : null;
    }
}
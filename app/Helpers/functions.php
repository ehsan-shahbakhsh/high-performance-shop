<?php

if (! function_exists('convert_to_english_digits')) {
    /**
     * Convert Persian/Arabic numerals to English numerals.
     *
     * @param string|null $string
     * @return string
     */
    function convert_to_english_digits(?string $string): string
    {
        if (empty($string)) {
            return '';
        }

        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $num = range(0, 9);

        $converted = str_replace($persian, $num, $string);
        return str_replace($arabic, $num, $converted);
    }
}

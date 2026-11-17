<?php

namespace App\Support;

use Illuminate\Support\Str;

class SafeRichText
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><ul><ol><li><h2><h3>';

    public static function sanitize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value);
        $value = preg_replace('/<div\b[^>]*>/i', '<p>', $value);
        $value = preg_replace('/<\/div>/i', '</p>', $value);
        $value = strip_tags($value, self::ALLOWED_TAGS);
        $value = preg_replace_callback(
            '/<(p|br|strong|b|em|i|ul|ol|li|h2|h3)\b[^>]*>/i',
            fn (array $match) => '<'.Str::lower($match[1]).'>',
            $value
        );
        $value = str_ireplace(['<b>', '</b>', '<i>', '</i>'], ['<strong>', '</strong>', '<em>', '</em>'], $value);

        return trim($value);
    }

    public static function render(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        if ($value === strip_tags($value)) {
            return nl2br(e($value));
        }

        return self::sanitize($value) ?? '';
    }
}

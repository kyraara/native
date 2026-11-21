<?php

namespace App\Support;

class Phone
{
    /** "0812-3456 7890" / "+62 812…" / "812…" → "62812…" (digits only). */
    public static function normalize(mixed $phone): string
    {
        $digits = preg_replace('/\D+/', '', is_string($phone) ? $phone : '');

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}

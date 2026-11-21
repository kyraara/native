<?php

namespace App\Support;

class Money
{
    /** "Rp 175.000", or "Rp 275.000,50" when there are cents. */
    public static function rupiah(float|int|string|null $amount, string $empty = '—'): string
    {
        if ($amount === null || $amount === '') {
            return $empty;
        }

        $value = (float) $amount;
        $decimals = fmod($value, 1.0) === 0.0 ? 0 : 2;

        return 'Rp '.number_format($value, $decimals, ',', '.');
    }
}

<?php

namespace App\Support;

final class Money
{
    private const MAX_CENTS = 9_999_999_999;

    public static function toCents(mixed $value): ?int
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            return null;
        }

        $whole = ltrim($matches[1], '0');
        $whole = $whole === '' ? '0' : $whole;

        if (strlen($whole) > 8) {
            return null;
        }

        $decimal = str_pad($matches[2] ?? '', 2, '0');
        $cents = ((int) $whole * 100) + (int) $decimal;

        return $cents <= self::MAX_CENTS ? $cents : null;
    }

    public static function fromCents(int $cents): string
    {
        $whole = intdiv($cents, 100);
        $decimal = abs($cents % 100);

        return $whole.'.'.str_pad((string) $decimal, 2, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Support;

/**
 * Single place for the system's money rounding/formatting policy (ETB, 2 dp,
 * half-up). Routing all payroll arithmetic through here keeps rounding
 * consistent and gives one seam to switch to integer-cents storage later
 * without touching every call site.
 */
class Money
{
    /** Round a monetary amount to 2 decimal places (half-up). */
    public static function round(float $amount): float
    {
        return round($amount, 2);
    }

    /** Format for display, e.g. 1234.5 → "1,234.50". */
    public static function format(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2);
    }

    /** Convert ETB to integer cents (for exact storage/transport). */
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /** Convert integer cents back to ETB. */
    public static function fromCents(int $cents): float
    {
        return $cents / 100;
    }
}

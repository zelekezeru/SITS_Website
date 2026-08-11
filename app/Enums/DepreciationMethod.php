<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * How an asset loses book value over its useful life.
 *
 * Reporting only — the asset register computes and shows depreciation, but
 * nothing posts it to a general ledger, because there is no GL to post to.
 * See docs/inventory-management-design.md §7.
 */
enum DepreciationMethod: string
{
    use HasLabel;

    case None             = 'none';              // land, or items not depreciated
    case StraightLine     = 'straight_line';     // equal charge each period
    case DecliningBalance = 'declining_balance'; // fixed % of the reducing book value

    public function label(): string
    {
        return match ($this) {
            self::None             => 'Not depreciated',
            self::StraightLine     => 'Straight line',
            self::DecliningBalance => 'Declining balance',
        };
    }

    /**
     * Accumulated depreciation after $monthsElapsed, given cost, salvage value
     * and useful life. Never depreciates below salvage value.
     */
    public function accumulated(float $cost, float $salvage, int $usefulLifeMonths, int $monthsElapsed): float
    {
        if ($this === self::None || $usefulLifeMonths <= 0 || $monthsElapsed <= 0) {
            return 0.0;
        }

        $depreciable = max($cost - $salvage, 0.0);
        $months = min($monthsElapsed, $usefulLifeMonths);

        if ($this === self::StraightLine) {
            return round($depreciable * ($months / $usefulLifeMonths), 2);
        }

        // Declining balance at double the straight-line rate, applied monthly.
        $monthlyRate = 2 / $usefulLifeMonths;
        $book = $cost;

        for ($m = 0; $m < $months; $m++) {
            $charge = $book * $monthlyRate;
            if ($book - $charge < $salvage) {
                $charge = max($book - $salvage, 0.0);
            }
            $book -= $charge;
        }

        return round($cost - $book, 2);
    }
}

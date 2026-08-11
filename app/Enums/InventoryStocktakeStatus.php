<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Physical count session lifecycle. The split between Review and Posted is the
 * control that matters: counting is the store keeper's job, but turning a
 * variance into a stock adjustment needs StorePermission::ADJUST.
 */
enum InventoryStocktakeStatus: string
{
    use HasLabel;

    case Open      = 'open';      // scope defined, system quantities snapshotted
    case Counting  = 'counting';  // counters entering physical figures
    case Review    = 'review';    // counted, variances awaiting a checker
    case Posted    = 'posted';    // variances written to the ledger as adjustments
    case Cancelled = 'cancelled';

    /** Counters may still enter or change figures. */
    public function acceptsCounts(): bool
    {
        return in_array($this, [self::Open, self::Counting], true);
    }

    /** Ready for a checker to post — requires the adjust permission. */
    public function isPostable(): bool
    {
        return $this === self::Review;
    }

    /** Invariant 6: posting is idempotent, so a posted session is frozen. */
    public function isClosed(): bool
    {
        return in_array($this, [self::Posted, self::Cancelled], true);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'slate',
            self::Counting => 'blue',
            self::Review => 'amber',
            self::Posted => 'emerald',
            self::Cancelled => 'rose',
        };
    }
}

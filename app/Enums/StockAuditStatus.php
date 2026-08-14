<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * A physical count. Corrections are posted to the ledger only on APPROVED — a
 * counter can never move stock on their own signature.
 */
enum StockAuditStatus: string
{
    use HasLabel;

    case DRAFT       = 'draft';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED   = 'completed';   // counting finished, variance awaiting sign-off
    case APPROVED    = 'approved';    // variance accepted, corrections posted
    case CANCELLED   = 'cancelled';

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT       => 'gray',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED   => 'amber',
            self::APPROVED    => 'green',
            self::CANCELLED   => 'rose',
        };
    }

    public function acceptsCounts(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::CANCELLED], true);
    }
}

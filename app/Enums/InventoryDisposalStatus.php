<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Disposal is the classic loss vector, so it never happens in one step: the
 * store keeper proposes, a checker with StorePermission::APPROVE_DISPOSAL
 * decides, and only then does the asset leave the register.
 */
enum InventoryDisposalStatus: string
{
    use HasLabel;

    case Proposed  = 'proposed';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Completed = 'completed'; // approved and the movement has been posted
    case Cancelled = 'cancelled';

    public function awaitsApproval(): bool
    {
        return $this === self::Proposed;
    }

    /** Approved but not yet posted to the ledger. */
    public function isPostable(): bool
    {
        return $this === self::Approved;
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled], true);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Proposed => 'amber',
            self::Approved => 'blue',
            self::Completed => 'emerald',
            self::Rejected, self::Cancelled => 'rose',
        };
    }
}

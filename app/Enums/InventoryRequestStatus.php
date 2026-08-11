<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Requisition lifecycle. Maker-checker, matching the shape KPIs, mass
 * permissions and medical allowance already use:
 *
 *   draft → submitted → approved → partially_fulfilled → fulfilled
 *                     ↘ rejected              ↘ cancelled
 */
enum InventoryRequestStatus: string
{
    use HasLabel;

    case Draft              = 'draft';
    case Submitted          = 'submitted';
    case Approved           = 'approved';
    case PartiallyFulfilled = 'partially_fulfilled';
    case Fulfilled          = 'fulfilled';
    case Rejected           = 'rejected';
    case Cancelled          = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft              => 'Draft',
            self::Submitted          => 'Awaiting approval',
            self::Approved           => 'Approved',
            self::PartiallyFulfilled => 'Partially issued',
            self::Fulfilled          => 'Fulfilled',
            self::Rejected           => 'Rejected',
            self::Cancelled          => 'Cancelled',
        };
    }

    /** The requester can still edit or withdraw it. */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /** Sitting in an approver's queue. */
    public function awaitsApproval(): bool
    {
        return $this === self::Submitted;
    }

    /** The store may issue against it. */
    public function isIssuable(): bool
    {
        return in_array($this, [self::Approved, self::PartiallyFulfilled], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Fulfilled, self::Rejected, self::Cancelled], true);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Submitted => 'amber',
            self::Approved => 'blue',
            self::PartiallyFulfilled => 'violet',
            self::Fulfilled => 'emerald',
            self::Rejected, self::Cancelled => 'rose',
        };
    }
}

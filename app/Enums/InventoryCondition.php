<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Physical condition, recorded on arrival, on every handover in each direction,
 * and at each stocktake. Comparing condition_out with condition_in on an asset
 * assignment is what makes damage attributable.
 */
enum InventoryCondition: string
{
    use HasLabel;

    case New           = 'new';
    case Good          = 'good';
    case Fair          = 'fair';
    case Poor          = 'poor';
    case Damaged       = 'damaged';
    case Unserviceable = 'unserviceable';

    /** Ordinal rank, so a handover can detect deterioration. Higher is better. */
    public function rank(): int
    {
        return match ($this) {
            self::New           => 5,
            self::Good          => 4,
            self::Fair          => 3,
            self::Poor          => 2,
            self::Damaged       => 1,
            self::Unserviceable => 0,
        };
    }

    /** True when handing back in $this condition is worse than it went out. */
    public function isWorseThan(self $other): bool
    {
        return $this->rank() < $other->rank();
    }

    /** Beyond economical repair — a disposal candidate. */
    public function isEndOfLife(): bool
    {
        return $this === self::Unserviceable;
    }

    public function tone(): string
    {
        return match ($this) {
            self::New, self::Good => 'emerald',
            self::Fair => 'blue',
            self::Poor => 'amber',
            self::Damaged, self::Unserviceable => 'rose',
        };
    }
}

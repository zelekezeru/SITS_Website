<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BookPaymentStatus: string
{
    use HasLabel;

    case PENDING  = 'pending';    // recorded by the requester/clerk, awaiting finance
    case VERIFIED = 'verified';   // finance matched it to the bank statement / CRV book
    case REJECTED = 'rejected';   // reference or receipt did not check out

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING  => 'amber',
            self::VERIFIED => 'green',
            self::REJECTED => 'red',
        };
    }

    /** Only verified money counts toward what a request has settled. */
    public function countsTowardSettlement(): bool
    {
        return $this === self::VERIFIED;
    }
}

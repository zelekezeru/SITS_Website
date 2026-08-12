<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * A "pay later" request: Finance asks for the payment gate to be released
 * before the money is in, and an authoriser accepts the debt.
 *
 * Deliberately its own record rather than a flag on the request — a deferral is
 * a decision two named people made, with a reason and a justification, and it
 * has to survive being asked about a year later.
 */
enum PaymentBypassStatus: string
{
    use HasLabel;

    case PENDING  = 'pending';   // raised by Finance, awaiting an authoriser
    case APPROVED = 'approved';  // debt accepted; the payment gate opens
    case REJECTED = 'rejected';  // declined; the request stays at the payment gate
    case SETTLED  = 'settled';   // the deferred money was eventually paid

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING  => 'amber',
            self::APPROVED => 'purple',
            self::REJECTED => 'red',
            self::SETTLED  => 'green',
        };
    }

    /** Whether this bypass currently opens the payment gate. */
    public function releasesPaymentGate(): bool
    {
        return $this === self::APPROVED || $this === self::SETTLED;
    }

    /** Money still owed under this deferral, i.e. it needs chasing. */
    public function isOutstandingDebt(): bool
    {
        return $this === self::APPROVED;
    }
}

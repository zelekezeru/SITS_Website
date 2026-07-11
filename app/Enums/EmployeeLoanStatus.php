<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EmployeeLoanStatus: string
{
    use HasLabel;

    case Active = 'active';        // still owing, deducted each payroll
    case Paid = 'paid';            // fully repaid
    case Cancelled = 'cancelled';  // written off / voided

    /** Whether payroll should still deduct against this loan. */
    public function isOutstanding(): bool
    {
        return $this === self::Active;
    }
}

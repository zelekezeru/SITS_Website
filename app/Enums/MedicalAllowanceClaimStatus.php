<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum MedicalAllowanceClaimStatus: string
{
    use HasLabel;

    case PendingReview = 'pending_review'; // submitted by Finance, awaiting admin decision
    case Approved = 'approved';            // admin approved; coverage split locked in, awaiting disbursement
    case Rejected = 'rejected';            // admin declined the claim
    case Paid = 'paid';                    // reimbursement disbursed and recorded
    case Cancelled = 'cancelled';          // withdrawn by Finance before review

    /** Whether this claim's covered_amount counts toward the employee's yearly usage. */
    public function reservesCoverage(): bool
    {
        return $this === self::Approved || $this === self::Paid;
    }
}

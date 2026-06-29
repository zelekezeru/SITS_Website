<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PayrollStatus: string
{
    use HasLabel;

    case Open = 'open';
    case Processing = 'processing';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Locked = 'locked';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending Approval',
            default => ucfirst(str_replace('_', ' ', $this->value)),
        };
    }
}

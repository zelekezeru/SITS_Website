<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** What an actor did at a workflow stage. Recorded once per stage, never edited. */
enum ApprovalDecision: string
{
    use HasLabel;

    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case RETURNED = 'returned';   // sent back to the requester for correction

    public function badgeColor(): string
    {
        return match ($this) {
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::RETURNED => 'amber',
        };
    }
}

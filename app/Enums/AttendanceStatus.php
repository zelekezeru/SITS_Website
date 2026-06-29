<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AttendanceStatus: string
{
    use HasLabel;

    case Raw = 'raw';
    case PendingReview = 'pending_review';
    case Verified = 'verified';
    case Locked = 'locked';
}

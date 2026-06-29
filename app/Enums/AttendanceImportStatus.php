<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AttendanceImportStatus: string
{
    use HasLabel;

    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

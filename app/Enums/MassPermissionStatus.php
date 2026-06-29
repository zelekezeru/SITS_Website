<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum MassPermissionStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case PendingConfirmation = 'pending_confirmation';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

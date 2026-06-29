<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum IncrementStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Applied = 'applied';
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PayslipStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Locked = 'locked';
    case Paid = 'paid';
}

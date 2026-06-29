<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum KpiStatus: string
{
    use HasLabel;

    case Created = 'created';
    case InProgress = 'in_progress';
    case Achieved = 'achieved';
}

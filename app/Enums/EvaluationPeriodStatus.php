<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EvaluationPeriodStatus: string
{
    use HasLabel;

    case Open = 'open';
    case InReview = 'in_review';
    case Calibrated = 'calibrated';
    case Locked = 'locked';
}

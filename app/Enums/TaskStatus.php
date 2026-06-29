<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum TaskStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Completed = 'completed';
    case Missed = 'missed';
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EmploymentType: string
{
    use HasLabel;

    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Probation = 'probation';
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EvaluationStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case EmployeeAck = 'employee_ack';
    case Disputed = 'disputed';
    case Finalized = 'finalized';
}

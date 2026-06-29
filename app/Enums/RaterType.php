<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum RaterType: string
{
    use HasLabel;

    case System = 'system';
    case Manager = 'manager';
    case DepartmentHead = 'department_head';
    case Executive = 'executive';
}

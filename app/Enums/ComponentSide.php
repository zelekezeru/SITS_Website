<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** Which party bears a statutory contribution. */
enum ComponentSide: string
{
    use HasLabel;

    case Employee = 'employee';   // deducted from the employee's net pay
    case Employer = 'employer';   // employer cost — shown but not deducted
}

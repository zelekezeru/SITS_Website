<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EmployeeLoanPaymentType: string
{
    use HasLabel;

    case Payroll = 'payroll';   // auto-withheld on a payroll run
    case Manual = 'manual';     // extra / lump-sum / settlement paid outside payroll
}

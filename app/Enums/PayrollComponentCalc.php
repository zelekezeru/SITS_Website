<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PayrollComponentCalc: string
{
    use HasLabel;

    case Fixed = 'fixed';       // a flat ETB amount (from the assignment)
    case Percent = 'percent';   // a percentage of base salary (rate on the component)
}

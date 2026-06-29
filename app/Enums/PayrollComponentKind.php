<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PayrollComponentKind: string
{
    use HasLabel;

    case Allowance = 'allowance';   // earning — adds to gross
    case Deduction = 'deduction';   // post-tax deduction from net
    case Statutory = 'statutory';   // pension / provident fund (employee or employer side)

    public function isEarning(): bool
    {
        return $this === self::Allowance;
    }
}

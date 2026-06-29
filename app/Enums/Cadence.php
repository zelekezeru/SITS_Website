<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Cadence: string
{
    use HasLabel;

    case Daily = 'daily';
    case Fortnightly = 'fortnightly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnual = 'semi_annual';
    case Annual = 'annual';
    case Custom = 'custom';
}

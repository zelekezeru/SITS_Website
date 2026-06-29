<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ClosedDayType: string
{
    use HasLabel;

    case Holiday = 'holiday';
    case SpecialClosure = 'special_closure';
    case Official = 'official';
}

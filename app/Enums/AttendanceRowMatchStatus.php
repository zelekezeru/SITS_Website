<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AttendanceRowMatchStatus: string
{
    use HasLabel;

    case Matched = 'matched';
    case Ambiguous = 'ambiguous';
    case Unmatched = 'unmatched';
}

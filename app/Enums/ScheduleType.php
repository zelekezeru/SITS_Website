<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** How a component assignment recurs across payroll periods. */
enum ScheduleType: string
{
    use HasLabel;

    case Monthly = 'monthly';     // every month (within optional start/end bounds)
    case Range = 'range';         // every month between start and end period (inclusive)
    case OneTime = 'one_time';    // a single period only

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly (recurring)',
            self::Range => 'Month range',
            self::OneTime => 'One-time',
        };
    }
}

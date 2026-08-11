<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** Why an asset went out of service. */
enum InventoryMaintenanceType: string
{
    use HasLabel;

    case Preventive  = 'preventive';  // scheduled servicing
    case Repair      = 'repair';      // something broke
    case Calibration = 'calibration'; // instruments, scales, projectors
    case Inspection  = 'inspection';  // safety or insurance check
    case Upgrade     = 'upgrade';     // RAM, tyres, firmware

    /**
     * Types that should schedule a next_due_at when completed — recurring by
     * nature, unlike a one-off repair.
     */
    public function isRecurring(): bool
    {
        return in_array($this, [self::Preventive, self::Calibration, self::Inspection], true);
    }

    /** Unplanned work — the basis of a breakdown-rate report. */
    public function isUnplanned(): bool
    {
        return $this === self::Repair;
    }
}

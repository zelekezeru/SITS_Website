<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** Where a serialized asset stands right now. */
enum InventoryUnitStatus: string
{
    use HasLabel;

    case InStore          = 'in_store';          // on the shelf, available to issue
    case Issued           = 'issued';            // in an employee's custody
    case Deployed         = 'deployed';          // installed in a room/office, not personally held
    case Reserved         = 'reserved';          // earmarked against an approved request
    case UnderMaintenance = 'under_maintenance'; // out for repair or calibration
    case Lost             = 'lost';              // unaccounted for after a stocktake
    case Disposed         = 'disposed';          // written off, sold, donated or scrapped

    public function label(): string
    {
        return match ($this) {
            self::InStore          => 'In store',
            self::Issued           => 'Issued',
            self::Deployed         => 'Deployed',
            self::Reserved         => 'Reserved',
            self::UnderMaintenance => 'Under maintenance',
            self::Lost             => 'Lost',
            self::Disposed         => 'Disposed',
        };
    }

    /** Can be issued to someone right now. */
    public function isAvailable(): bool
    {
        return $this === self::InStore;
    }

    /** Out of the store but still ours — counts as an asset on loan. */
    public function isOut(): bool
    {
        return in_array($this, [self::Issued, self::Deployed, self::UnderMaintenance], true);
    }

    /**
     * Terminal states accept no further movements — invariant 7 in
     * docs/inventory-management-design.md §5.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Disposed, self::Lost], true);
    }

    public function tone(): string
    {
        return match ($this) {
            self::InStore => 'emerald',
            self::Issued, self::Deployed => 'blue',
            self::Reserved => 'violet',
            self::UnderMaintenance => 'amber',
            self::Lost, self::Disposed => 'rose',
        };
    }
}

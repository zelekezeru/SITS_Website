<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * What a node in the location tree represents. The tree is generic rather than a
 * fixed campus→room→shelf triple, because a store cares about bins, offices and
 * vehicles at whatever depth the building actually has.
 */
enum InventoryLocationType: string
{
    use HasLabel;

    case Store    = 'store';    // a stockroom the store keeper controls
    case Building = 'building';
    case Floor    = 'floor';
    case Room     = 'room';
    case Shelf    = 'shelf';
    case Bin      = 'bin';
    case Office   = 'office';   // where deployed assets live
    case Vehicle  = 'vehicle';  // a van carrying tools counts as a location
    case External = 'external'; // off-site: a repair vendor, a loan to another institution

    /**
     * Types that can hold stock directly. A building or floor is a grouping —
     * you don't put a box of chalk "in a floor".
     */
    public function isStorable(): bool
    {
        return in_array($this, [
            self::Store, self::Room, self::Shelf, self::Bin,
            self::Office, self::Vehicle, self::External,
        ], true);
    }

    /** The store keeper's own domain, as opposed to where issued things end up. */
    public function isUnderStoreControl(): bool
    {
        return in_array($this, [self::Store, self::Shelf, self::Bin], true);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Store => 'Warehouse',
            self::Building => 'Building2',
            self::Floor => 'Layers',
            self::Room => 'MapPin',
            self::Shelf => 'Boxes',
            self::Bin => 'Package',
            self::Office => 'Briefcase',
            self::Vehicle => 'Truck',
            self::External => 'ExternalLink',
        };
    }
}

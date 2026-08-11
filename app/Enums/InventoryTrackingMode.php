<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * The branch the whole schema turns on: whether an item is counted in bulk or
 * tracked one physical thing at a time.
 *
 * See docs/inventory-management-design.md §1 — a quantity column cannot answer
 * "which laptop is with the Registrar", and an asset register cannot sanely
 * hold 500 pens.
 */
enum InventoryTrackingMode: string
{
    use HasLabel;

    /** Fungible and depleting — counted from the ledger, no per-unit rows. */
    case Consumable = 'consumable';

    /** Individually identifiable — one inventory_units row each, with a tag. */
    case Asset = 'asset';

    public function label(): string
    {
        return match ($this) {
            self::Consumable => 'Consumable stock',
            self::Asset      => 'Fixed asset',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Consumable => 'Counted in bulk and depleted on issue — paper, detergent, fuel.',
            self::Asset      => 'Tracked individually with an asset tag, custody chain and condition — laptops, furniture, vehicles.',
        };
    }

    /** Whether items in this mode carry inventory_units rows. */
    public function isSerialized(): bool
    {
        return $this === self::Asset;
    }
}

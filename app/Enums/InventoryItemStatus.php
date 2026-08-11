<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Catalog lifecycle, separate from stock level. An item can be Active with zero
 * on hand (out of stock, reorder it) or Discontinued with stock remaining (use
 * up what's left, don't buy more) — collapsing the two into one flag loses that.
 */
enum InventoryItemStatus: string
{
    use HasLabel;

    case Active       = 'active';
    case Discontinued = 'discontinued'; // run down remaining stock, don't reorder
    case Archived     = 'archived';     // historical only, hidden from pickers

    /** Whether the item may appear in a requisition or receiving picker. */
    public function isSelectable(): bool
    {
        return $this === self::Active;
    }

    /** Whether reorder alerts should fire for this item. */
    public function isReorderable(): bool
    {
        return $this === self::Active;
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Discontinued => 'amber',
            self::Archived => 'slate',
        };
    }
}

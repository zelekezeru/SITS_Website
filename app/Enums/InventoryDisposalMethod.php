<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/** How an asset or stock lot leaves the institution for good. */
enum InventoryDisposalMethod: string
{
    use HasLabel;

    case Sold               = 'sold';
    case Donated            = 'donated';
    case Scrapped           = 'scrapped';
    case WrittenOff         = 'written_off';         // damaged beyond repair
    case Lost               = 'lost';                // unaccounted for
    case ReturnedToSupplier = 'returned_to_supplier';
    case Expired            = 'expired';             // consumables past their date

    public function label(): string
    {
        return match ($this) {
            self::Sold               => 'Sold',
            self::Donated            => 'Donated',
            self::Scrapped           => 'Scrapped',
            self::WrittenOff         => 'Written off',
            self::Lost               => 'Lost',
            self::ReturnedToSupplier => 'Returned to supplier',
            self::Expired            => 'Expired',
        };
    }

    /** Methods that bring money in, so proceeds must be recorded. */
    public function yieldsProceeds(): bool
    {
        return $this === self::Sold;
    }

    /**
     * Whether the loss should be flagged for investigation rather than merely
     * recorded — these are the ones an auditor asks about.
     */
    public function isLossEvent(): bool
    {
        return in_array($this, [self::Lost, self::WrittenOff], true);
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Every way stock can change hands. The ledger stores a *signed* quantity and
 * this enum is the authority on the sign — see direction(). Nothing else in the
 * module may decide whether a movement adds or removes stock.
 *
 * A transfer is deliberately two rows (TransferOut then TransferIn) rather than
 * one row with two locations: that way summing movements per location gives the
 * right answer without special-casing transfers, and a transfer in flight
 * between campuses is visible as such.
 */
enum InventoryMovementType: string
{
    use HasLabel;

    case Receipt      = 'receipt';       // goods received from a supplier (GRN)
    case Issue        = 'issue';         // released to an employee or department
    case Return       = 'return';        // handed back into store
    case TransferOut  = 'transfer_out';  // left a location
    case TransferIn   = 'transfer_in';   // arrived at a location
    case Adjustment   = 'adjustment';    // variance correction; signed either way
    case WriteOff     = 'write_off';     // damaged, lost or expired
    case Disposal     = 'disposal';      // sold, donated or scrapped
    case Consumption  = 'consumption';   // used up in place (fuel, cleaning supplies)
    case OpeningStock = 'opening_stock'; // migration of pre-system balances

    /**
     * +1 adds to stock, −1 removes, 0 means the caller supplies the sign
     * (an adjustment can go either way).
     */
    public function direction(): int
    {
        return match ($this) {
            self::Receipt, self::Return, self::TransferIn, self::OpeningStock => 1,
            self::Issue, self::TransferOut, self::WriteOff, self::Disposal, self::Consumption => -1,
            self::Adjustment => 0,
        };
    }

    /** Whether the caller must supply the sign themselves. */
    public function isSignedByCaller(): bool
    {
        return $this->direction() === 0;
    }

    /** Movements that take stock out — the basis of the negative-stock guard. */
    public function isOutward(): bool
    {
        return $this->direction() === -1;
    }

    public function isInward(): bool
    {
        return $this->direction() === 1;
    }

    /**
     * Types only a checker may post. Adjustments are how a shrinkage variance
     * would be quietly erased, so they carry their own permission.
     *
     * @see \App\Enums\StorePermission::ADJUST
     */
    public function requiresAdjustPermission(): bool
    {
        return in_array($this, [self::Adjustment, self::WriteOff, self::OpeningStock], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Receipt      => 'Receipt (GRN)',
            self::Issue        => 'Issue',
            self::Return       => 'Return to store',
            self::TransferOut  => 'Transfer out',
            self::TransferIn   => 'Transfer in',
            self::Adjustment   => 'Adjustment',
            self::WriteOff     => 'Write-off',
            self::Disposal     => 'Disposal',
            self::Consumption  => 'Consumption',
            self::OpeningStock => 'Opening stock',
        };
    }

    /** Tailwind-ish tone used by the movement ledger table. */
    public function tone(): string
    {
        return match ($this) {
            self::Receipt, self::Return, self::TransferIn, self::OpeningStock => 'emerald',
            self::Issue, self::TransferOut, self::Consumption => 'amber',
            self::WriteOff, self::Disposal => 'rose',
            self::Adjustment => 'violet',
        };
    }
}

<?php

namespace App\Enums;

/**
 * Every line that can appear on a bin card (the paper "SBCE STORE LOG").
 *
 * Movements are append-only and their `quantity` is ALWAYS positive — the
 * direction of the change comes from the type, via {@see self::sign()}. A
 * mistake is never edited away; it is corrected with a compensating movement.
 */
enum StockMovementType: string
{
    case OPENING_BALANCE      = 'opening_balance';
    case RECEIPT              = 'receipt';               // print run delivered into the store
    case ISSUE                = 'issue';                 // dispatched to a centre / campus
    case RETURN_IN            = 'return';                // unsold copies coming back
    case TRANSFER_IN          = 'transfer_in';
    case TRANSFER_OUT         = 'transfer_out';
    case ADJUSTMENT_INCREASE  = 'adjustment_increase';
    case ADJUSTMENT_DECREASE  = 'adjustment_decrease';
    case DAMAGE               = 'damage';
    case LOSS                 = 'loss';

    // Variance settled by a signed-off stock audit. Two cases rather than one so
    // the sign is data, never a special case in the ledger.
    case AUDIT_SURPLUS        = 'audit_surplus';          // counted more than the system held
    case AUDIT_SHORTAGE       = 'audit_shortage';         // counted less

    public function label(): string
    {
        return match ($this) {
            self::OPENING_BALANCE     => 'Opening Balance',
            self::RECEIPT             => 'Receipt (Print Run)',
            self::ISSUE               => 'Issue / Dispatch',
            self::RETURN_IN           => 'Return',
            self::TRANSFER_IN         => 'Transfer In',
            self::TRANSFER_OUT        => 'Transfer Out',
            self::ADJUSTMENT_INCREASE => 'Adjustment (+)',
            self::ADJUSTMENT_DECREASE => 'Adjustment (−)',
            self::DAMAGE              => 'Damaged',
            self::LOSS                => 'Lost',
            self::AUDIT_SURPLUS       => 'Audit Correction (+)',
            self::AUDIT_SHORTAGE      => 'Audit Correction (−)',
        };
    }

    /** +1 when the movement adds to the shelf, -1 when it takes away. */
    public function sign(): int
    {
        return match ($this) {
            self::OPENING_BALANCE,
            self::RECEIPT,
            self::RETURN_IN,
            self::TRANSFER_IN,
            self::ADJUSTMENT_INCREASE,
            self::AUDIT_SURPLUS => 1,

            self::ISSUE,
            self::TRANSFER_OUT,
            self::ADJUSTMENT_DECREASE,
            self::DAMAGE,
            self::LOSS,
            self::AUDIT_SHORTAGE => -1,
        };
    }

    public function isInbound(): bool
    {
        return $this->sign() > 0;
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::OPENING_BALANCE     => 'gray',
            self::RECEIPT             => 'green',
            self::ISSUE               => 'blue',
            self::RETURN_IN           => 'teal',
            self::TRANSFER_IN,
            self::TRANSFER_OUT        => 'indigo',
            self::ADJUSTMENT_INCREASE => 'emerald',
            self::ADJUSTMENT_DECREASE => 'orange',
            self::DAMAGE              => 'rose',
            self::LOSS                => 'red',
            self::AUDIT_SURPLUS,
            self::AUDIT_SHORTAGE      => 'purple',
        };
    }

    /** Types a user may post by hand; the rest are written by the system. */
    public static function manual(): array
    {
        return [
            self::ADJUSTMENT_INCREASE,
            self::ADJUSTMENT_DECREASE,
            self::DAMAGE,
            self::LOSS,
        ];
    }

    /** ['value' => 'Label'] for <select> options. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }
}

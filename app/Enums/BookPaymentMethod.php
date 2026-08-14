<?php

namespace App\Enums;

enum BookPaymentMethod: string
{
    case CASH          = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CBE_BIRR      = 'cbe_birr';
    case TELEBIRR      = 'telebirr';
    case CHEQUE        = 'cheque';
    case WAIVER        = 'waiver';       // approved free issue — amount 0, still auditable

    public function label(): string
    {
        return match ($this) {
            self::CASH          => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CBE_BIRR      => 'CBE Birr',
            self::TELEBIRR      => 'Telebirr',
            self::CHEQUE        => 'Cheque',
            self::WAIVER        => 'Waiver / Free Issue',
        };
    }

    /** Methods that must carry a bank/wallet transaction reference. */
    public function requiresTransactionReference(): bool
    {
        return in_array($this, [
            self::BANK_TRANSFER,
            self::CBE_BIRR,
            self::TELEBIRR,
            self::CHEQUE,
        ], true);
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }
}

<?php

namespace App\Enums;

/**
 * The lifecycle of a book request — a direct digitisation of the four signature
 * blocks on the paper "የመጽሃፍት መጠየቂያ ቅጽ" (book request form).
 *
 *   DRAFT → SUBMITTED → AWAITING_PAYMENT → PAYMENT_VERIFIED → APPROVED
 *         → (PARTIALLY_DISPATCHED) → DISPATCHED → RECEIVED
 *
 * The allowed edges live here — not in a controller — so the UI, the API and
 * any future import all obey the same rules.
 */
enum BookRequestStatus: string
{
    case DRAFT                = 'draft';
    case SUBMITTED            = 'submitted';             // awaiting availability/genuineness check
    case AWAITING_PAYMENT     = 'awaiting_payment';      // verified & stock reserved; finance to confirm
    case PAYMENT_VERIFIED     = 'payment_verified';      // finance confirmed; awaiting final approval
    case APPROVED             = 'approved';              // cleared for the store to dispatch
    case PARTIALLY_DISPATCHED = 'partially_dispatched';
    case DISPATCHED           = 'dispatched';
    case RECEIVED             = 'received';              // receiver confirmed — terminal
    case REJECTED             = 'rejected';              // terminal
    case CANCELLED            = 'cancelled';             // terminal

    public function label(): string
    {
        return match ($this) {
            self::DRAFT                => 'Draft',
            self::SUBMITTED            => 'Submitted',
            self::AWAITING_PAYMENT     => 'Awaiting Payment',
            self::PAYMENT_VERIFIED     => 'Payment Verified',
            self::APPROVED             => 'Approved',
            self::PARTIALLY_DISPATCHED => 'Partially Dispatched',
            self::DISPATCHED           => 'Dispatched',
            self::RECEIVED             => 'Received',
            self::REJECTED             => 'Rejected',
            self::CANCELLED            => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT                => 'gray',
            self::SUBMITTED            => 'blue',
            self::AWAITING_PAYMENT     => 'amber',
            self::PAYMENT_VERIFIED     => 'cyan',
            self::APPROVED             => 'indigo',
            self::PARTIALLY_DISPATCHED => 'orange',
            self::DISPATCHED           => 'teal',
            self::RECEIVED             => 'green',
            self::REJECTED             => 'red',
            self::CANCELLED            => 'rose',
        };
    }

    /** Step number for the progress tracker in the UI (0 for terminal failures). */
    public function step(): int
    {
        return match ($this) {
            self::DRAFT                                     => 1,
            self::SUBMITTED                                 => 2,
            self::AWAITING_PAYMENT                          => 3,
            self::PAYMENT_VERIFIED                          => 4,
            self::APPROVED                                  => 5,
            self::PARTIALLY_DISPATCHED, self::DISPATCHED    => 6,
            self::RECEIVED                                  => 7,
            self::REJECTED, self::CANCELLED                 => 0,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::RECEIVED, self::REJECTED, self::CANCELLED], true);
    }

    /** Statuses whose reservations still hold stock back from other requests. */
    public function holdsReservation(): bool
    {
        return in_array($this, [
            self::AWAITING_PAYMENT,
            self::PAYMENT_VERIFIED,
            self::APPROVED,
            self::PARTIALLY_DISPATCHED,
        ], true);
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::SUBMITTED,
            self::AWAITING_PAYMENT,
            self::PAYMENT_VERIFIED,
        ], true);
    }

    /** The statuses reachable from this one (rejection/cancellation excluded). */
    public function next(): array
    {
        return match ($this) {
            self::DRAFT                => [self::SUBMITTED],
            self::SUBMITTED            => [self::AWAITING_PAYMENT],
            self::AWAITING_PAYMENT     => [self::PAYMENT_VERIFIED],
            self::PAYMENT_VERIFIED     => [self::APPROVED],
            self::APPROVED             => [self::PARTIALLY_DISPATCHED, self::DISPATCHED],
            self::PARTIALLY_DISPATCHED => [self::PARTIALLY_DISPATCHED, self::DISPATCHED],
            self::DISPATCHED           => [self::RECEIVED],
            default                    => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        if ($target === self::CANCELLED) {
            return $this->canBeCancelled();
        }

        if ($target === self::REJECTED) {
            return in_array($this, [self::SUBMITTED, self::AWAITING_PAYMENT, self::PAYMENT_VERIFIED], true);
        }

        return in_array($target, $this->next(), true);
    }

    /** Statuses an approver dashboard should treat as "open work". */
    public static function open(): array
    {
        return [
            self::SUBMITTED,
            self::AWAITING_PAYMENT,
            self::PAYMENT_VERIFIED,
            self::APPROVED,
            self::PARTIALLY_DISPATCHED,
            self::DISPATCHED,
        ];
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

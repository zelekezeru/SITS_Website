<?php

namespace App\Enums;

/**
 * Every point in the request journey where somebody needs to be told something.
 *
 * Each case knows who to tell and what to say, so the notifier stays a thin
 * dispatcher and the wording of the whole workflow lives in one readable place.
 */
enum BookRequestEvent: string
{
    case SUBMITTED         = 'submitted';           // → store manager: verify availability
    case VERIFIED          = 'verified';            // → requester + finance: pay / expect payment
    case PAYMENT_RECORDED  = 'payment_recorded';    // → finance: money claimed, confirm receipt
    case PAYMENT_VERIFIED  = 'payment_verified';    // → admin: ready for final approval
    case BYPASS_REQUESTED  = 'bypass_requested';    // → authoriser: pay-later needs a decision
    case BYPASS_APPROVED   = 'bypass_approved';     // → finance + requester
    case BYPASS_REJECTED   = 'bypass_rejected';     // → finance
    case APPROVED          = 'approved';            // → store: dispatch it
    case DISPATCHED        = 'dispatched';          // → requester: it is on its way
    case RECEIVED          = 'received';            // → store + requester: closed
    case REJECTED          = 'rejected';            // → requester

    public function subject(): string
    {
        return match ($this) {
            self::SUBMITTED        => 'Book request awaiting your availability check',
            self::VERIFIED         => 'Book request verified — payment now due',
            self::PAYMENT_RECORDED => 'A payment was recorded and needs verifying',
            self::PAYMENT_VERIFIED => 'Book request awaiting your final approval',
            self::BYPASS_REQUESTED => 'Pay-later deferral needs your authorisation',
            self::BYPASS_APPROVED  => 'Pay-later deferral approved',
            self::BYPASS_REJECTED  => 'Pay-later deferral declined',
            self::APPROVED         => 'Book request approved — ready to dispatch',
            self::DISPATCHED       => 'Your books have been dispatched',
            self::RECEIVED         => 'Book request closed — delivery confirmed',
            self::REJECTED         => 'Your book request was rejected',
        };
    }

    /** The one thing the recipient should do about it. */
    public function callToAction(): string
    {
        return match ($this) {
            self::SUBMITTED        => 'Check what the shelves can cover and reserve it.',
            self::VERIFIED         => 'Stock is reserved. Settle the payment with Finance.',
            self::PAYMENT_RECORDED => 'Match it against the bank record or the CRV book.',
            self::PAYMENT_VERIFIED => 'The money is confirmed. Give final approval to release it.',
            self::BYPASS_REQUESTED => 'Approve only with a written justification — this accepts a debt.',
            self::BYPASS_APPROVED  => 'The payment gate is open; the amount stays owed until settled.',
            self::BYPASS_REJECTED  => 'The request stays at the payment gate until it is paid.',
            self::APPROVED         => 'Pick the books and raise the waybill.',
            self::DISPATCHED       => 'Confirm receipt when the consignment arrives.',
            self::RECEIVED         => 'Nothing further is needed.',
            self::REJECTED         => 'Read the reason and raise a corrected request if appropriate.',
        };
    }

    /**
     * Permissions whose holders should be told. Empty means the notification is
     * for named individuals only (the requester), handled by the notifier.
     *
     * @return array<int, Permission>
     */
    public function notifyPermissions(): array
    {
        return match ($this) {
            self::SUBMITTED        => [Permission::VERIFY_BOOK_REQUEST],
            self::VERIFIED         => [Permission::VERIFY_BOOK_PAYMENT],
            self::PAYMENT_RECORDED => [Permission::VERIFY_BOOK_PAYMENT],
            self::PAYMENT_VERIFIED => [Permission::APPROVE_BOOK_REQUEST],
            self::BYPASS_REQUESTED => [Permission::APPROVE_PAYMENT_BYPASS],
            self::BYPASS_APPROVED,
            self::BYPASS_REJECTED  => [Permission::VERIFY_BOOK_PAYMENT],
            self::APPROVED         => [Permission::DISPATCH_BOOKS],
            self::RECEIVED         => [Permission::DISPATCH_BOOKS],
            default                => [],
        };
    }

    /** Whether the person who raised the request should also hear about it. */
    public function notifiesRequester(): bool
    {
        return in_array($this, [
            self::VERIFIED,
            self::APPROVED,
            self::DISPATCHED,
            self::RECEIVED,
            self::REJECTED,
            self::BYPASS_APPROVED,
        ], true);
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::REJECTED, self::BYPASS_REJECTED => 'red',
            self::RECEIVED, self::BYPASS_APPROVED => 'green',
            self::APPROVED                        => 'indigo',
            self::DISPATCHED                      => 'teal',
            self::BYPASS_REQUESTED                => 'purple',
            default                               => 'amber',
        };
    }
}

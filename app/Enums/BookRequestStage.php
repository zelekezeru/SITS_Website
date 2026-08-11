<?php

namespace App\Enums;

/**
 * One stage per signature block on the paper request form. Each stage names the
 * permission that gates it, so segregation of duties is data, not convention.
 */
enum BookRequestStage: string
{
    case SUBMISSION       = 'submission';        // requester signs and sends
    case VERIFICATION     = 'verification';      // operations: availability & genuineness
    case PAYMENT          = 'payment';           // finance: money received / receiver debt-free
    case APPROVAL         = 'approval';          // admin: final authorisation
    case DISPATCH         = 'dispatch';          // store manager releases the stock
    case RECEIPT          = 'receipt';           // receiver confirms delivery

    public function label(): string
    {
        return match ($this) {
            self::SUBMISSION   => 'Submission',
            self::VERIFICATION => 'Availability Verification',
            self::PAYMENT      => 'Payment Verification',
            self::APPROVAL     => 'Final Approval',
            self::DISPATCH     => 'Dispatch',
            self::RECEIPT      => 'Receipt Confirmation',
        };
    }

    /** The Amharic caption used on the printed form, kept for continuity. */
    public function captionAm(): string
    {
        return match ($this) {
            self::SUBMISSION   => 'የአስተባባሪ ስም',
            self::VERIFICATION => 'ያረጋገጠ ኦፕሬሽን ማናጀር',
            self::PAYMENT      => 'ገንዘብ ክፍል',
            self::APPROVAL     => 'የርቀት ትምህርት አስተባባሪ',
            self::DISPATCH     => 'ንብረት ክፍል ኃላፊ',
            self::RECEIPT      => 'ተረካቢ',
        };
    }

    public function permission(): Permission
    {
        return match ($this) {
            self::SUBMISSION   => Permission::REQUEST_BOOKS,
            self::VERIFICATION => Permission::VERIFY_BOOK_REQUEST,
            self::PAYMENT      => Permission::VERIFY_BOOK_PAYMENT,
            self::APPROVAL     => Permission::APPROVE_BOOK_REQUEST,
            self::DISPATCH     => Permission::DISPATCH_BOOKS,
            self::RECEIPT      => Permission::RECEIVE_BOOKS,
        };
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

<?php

namespace App\Enums;

enum BookStatus: string
{
    case AVAILABLE         = 'available';
    case CHECKED_OUT       = 'checked_out';
    case ON_HOLD           = 'on_hold';
    case IN_TRANSIT        = 'in_transit';
    case PENDING_TRANSFER  = 'pending_transfer';
    case WITHDRAWN         = 'withdrawn';
    case LOST              = 'lost';
    case DAMAGED           = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE        => 'Available',
            self::CHECKED_OUT      => 'Checked Out',
            self::ON_HOLD          => 'On Hold',
            self::IN_TRANSIT       => 'In Transit',
            self::PENDING_TRANSFER => 'Pending Transfer',
            self::WITHDRAWN        => 'Withdrawn',
            self::LOST             => 'Lost',
            self::DAMAGED          => 'Damaged',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::AVAILABLE        => 'green',
            self::CHECKED_OUT      => 'blue',
            self::ON_HOLD          => 'purple',
            self::IN_TRANSIT       => 'yellow',
            self::PENDING_TRANSFER => 'orange',
            self::WITHDRAWN        => 'gray',
            self::LOST             => 'red',
            self::DAMAGED          => 'rose',
        };
    }

    /** Returns statuses that count as "unavailable" for circulation purposes. */
    public static function unavailable(): array
    {
        return [
            self::CHECKED_OUT,
            self::ON_HOLD,
            self::IN_TRANSIT,
            self::PENDING_TRANSFER,
            self::WITHDRAWN,
            self::LOST,
            self::DAMAGED,
        ];
    }
}

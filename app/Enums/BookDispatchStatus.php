<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BookDispatchStatus: string
{
    use HasLabel;

    case PREPARED  = 'prepared';    // waybill created, stock already deducted
    case IN_TRANSIT = 'in_transit';
    case RECEIVED  = 'received';    // receiver scanned the waybill QR / signed
    case RETURNED  = 'returned';    // whole consignment came back

    public function badgeColor(): string
    {
        return match ($this) {
            self::PREPARED   => 'gray',
            self::IN_TRANSIT => 'blue',
            self::RECEIVED   => 'green',
            self::RETURNED   => 'orange',
        };
    }
}

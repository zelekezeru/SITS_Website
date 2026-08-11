<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Books go one of two ways out of the store: to a distribution centre (handed to
 * the centre's coordinator against a student count) or to a campus (handed to
 * campus representatives for direct student issue).
 */
enum RequestDestination: string
{
    use HasLabel;

    case CENTER = 'center';
    case CAMPUS = 'campus';

    public function label(): string
    {
        return match ($this) {
            self::CENTER => 'Distribution Centre',
            self::CAMPUS => 'Campus',
        };
    }
}

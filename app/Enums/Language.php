<?php

namespace App\Enums;

enum Language: string
{
    case En = 'en';
    case Am = 'am';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::En => 'English',
            self::Am => 'Amharic',
            self::Mixed => 'Mixed',
        };
    }
}

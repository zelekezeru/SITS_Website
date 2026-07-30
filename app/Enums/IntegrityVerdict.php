<?php

namespace App\Enums;

enum IntegrityVerdict: string
{
    case LIKELY_HUMAN = 'likely_human';
    case MIXED = 'mixed';
    case LIKELY_AI = 'likely_ai';
    case INSUFFICIENT_TEXT = 'insufficient_text';

    public function label(): string
    {
        return match ($this) {
            self::LIKELY_HUMAN => 'Likely Human-Written',
            self::MIXED => 'Mixed Signals',
            self::LIKELY_AI => 'Likely AI-Generated',
            self::INSUFFICIENT_TEXT => 'Insufficient Text',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::LIKELY_HUMAN => 'green',
            self::MIXED => 'amber',
            self::LIKELY_AI => 'red',
            self::INSUFFICIENT_TEXT => 'gray',
        };
    }
}

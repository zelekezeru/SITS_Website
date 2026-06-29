<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ConductSeverity: string
{
    use HasLabel;

    case Minor = 'minor';
    case Moderate = 'moderate';
    case Major = 'major';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Minor => 'Minor',
            self::Moderate => 'Moderate',
            self::Major => 'Major',
            self::Critical => 'Critical',
        };
    }

    public function amharicLabel(): string
    {
        return match ($this) {
            self::Minor => 'ትንንሽ',
            self::Moderate => 'መካከለኛ',
            self::Major => 'ብዙ',
            self::Critical => 'ወሳኝ',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Minor => 'yellow',
            self::Moderate => 'orange',
            self::Major => 'red',
            self::Critical => 'darkred',
        };
    }
}

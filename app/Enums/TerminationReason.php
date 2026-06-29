<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum TerminationReason: string
{
    use HasLabel;

    case Resignation = 'resignation';
    case Dismissal = 'dismissal';
    case Retirement = 'retirement';
    case ContractEnd = 'contract_end';
    case Redundancy = 'redundancy';
    case Death = 'death';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Resignation => 'Resignation',
            self::Dismissal => 'Dismissal',
            self::Retirement => 'Retirement',
            self::ContractEnd => 'Contract End',
            self::Redundancy => 'Redundancy',
            self::Death => 'Death',
            self::Other => 'Other',
        };
    }

    public function amharicLabel(): string
    {
        return match ($this) {
            self::Resignation => 'ከሥራ መውጣት',
            self::Dismissal => 'ሥራ እንደገና መወገድ',
            self::Retirement => 'ገበታ',
            self::ContractEnd => 'ስምምነት ማብቂያ',
            self::Redundancy => 'ግዛታ',
            self::Death => 'ሞት',
            self::Other => 'ሌላ',
        };
    }

    public function requiresSeverance(): bool
    {
        return !in_array($this, [self::Death, self::Retirement]);
    }

    public function isCauseForTermination(): bool
    {
        return in_array($this, [self::Dismissal, self::Redundancy]);
    }
}

<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ConductDecision: string
{
    use HasLabel;

    case NoAction = 'no_action';
    case Warning = 'warning';
    case Suspension = 'suspension';
    case Termination = 'termination';
    case Dismissal = 'dismissal';
    case RehabilitationProgram = 'rehabilitation_program';

    public function label(): string
    {
        return match ($this) {
            self::NoAction => 'No Action Required',
            self::Warning => 'Official Warning',
            self::Suspension => 'Suspension',
            self::Termination => 'Termination',
            self::Dismissal => 'Dismissal',
            self::RehabilitationProgram => 'Rehabilitation Program',
        };
    }

    public function amharicLabel(): string
    {
        return match ($this) {
            self::NoAction => 'ምንም ርምጃ አልተወሰደም',
            self::Warning => 'ኦፊሴላዊ ማስጠንቀቂያ',
            self::Suspension => 'ሥራ ማቆም',
            self::Termination => 'ስራ ማጣት',
            self::Dismissal => 'ሥራ እንደገና መወገድ',
            self::RehabilitationProgram => 'ማሻሻያ ፕሮግራም',
        };
    }

    public function requiresEmployeeStatusChange(): bool
    {
        return in_array($this, [
            self::Suspension,
            self::Termination,
            self::Dismissal,
        ]);
    }

    public function targetEmployeeStatus(): ?string
    {
        return match ($this) {
            self::Suspension => 'suspended',
            self::Termination, self::Dismissal => 'terminated',
            default => null,
        };
    }
}

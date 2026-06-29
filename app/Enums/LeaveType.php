<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum LeaveType: string
{
    use HasLabel;

    case Annual = 'annual';
    case Sick = 'sick';
    case Maternity = 'maternity';
    case Paternity = 'paternity';
    case Study = 'study';
    case Unpaid = 'unpaid';
    case Bereavement = 'bereavement';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Annual => 'Annual Leave',
            self::Sick => 'Sick Leave',
            self::Maternity => 'Maternity Leave',
            self::Paternity => 'Paternity Leave',
            self::Study => 'Study Leave',
            self::Unpaid => 'Unpaid Leave',
            self::Bereavement => 'Bereavement Leave',
            self::Other => 'Other Leave',
        };
    }

    public function amharicLabel(): string
    {
        return match ($this) {
            self::Annual => 'ዓመታዊ እረፍት',
            self::Sick => 'በሽታ እረፍት',
            self::Maternity => 'ወሊደት እረፍት',
            self::Paternity => '父 እረፍት',
            self::Study => 'ትምህርት እረፍት',
            self::Unpaid => 'ያልከፈለ እረፍት',
            self::Bereavement => 'ሞት እረፍት',
            self::Other => 'ሌላ እረፍት',
        };
    }

    public function requiresDocumentation(): bool
    {
        return in_array($this, [self::Sick, self::Maternity, self::Bereavement]);
    }

    public function isCountedAgainstBalance(): bool
    {
        return !in_array($this, [self::Unpaid, self::Other]);
    }
}

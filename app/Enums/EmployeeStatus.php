<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EmployeeStatus: string
{
    use HasLabel;

    case Active = 'active';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On Leave',
            self::Suspended => 'Suspended',
            self::Terminated => 'Terminated',
            self::Inactive => 'Inactive',
        };
    }

    public function amharicLabel(): string
    {
        return match ($this) {
            self::Active => 'ንቁ',
            self::OnLeave => 'በእረፍት ላይ',
            self::Suspended => 'ሥራ ተዮ',
            self::Terminated => 'ሥራ ያለሱ',
            self::Inactive => 'ንቁ ያልሆነ',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Terminated, self::Inactive]);
    }

    public function canTransitionTo(self $target): bool
    {
        // Define valid state transitions
        $validTransitions = [
            self::Active => [self::OnLeave, self::Suspended, self::Terminated, self::Inactive],
            self::OnLeave => [self::Active, self::Suspended, self::Terminated],
            self::Suspended => [self::Active, self::Terminated, self::Inactive],
            self::Terminated => [self::Inactive], // Can only become inactive after termination
            self::Inactive => [],
        ];

        return in_array($target, $validTransitions[$this] ?? []);
    }
}

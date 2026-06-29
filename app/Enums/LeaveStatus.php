<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum LeaveStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function amharicLabel(): string
    {
        return match ($this) {
            self::Draft => 'ረቂቅ',
            self::Submitted => 'ቀርቦ',
            self::Approved => 'ዋቅ ተሰጥቶ',
            self::Rejected => 'ተወገደ',
            self::Cancelled => 'ተሰርዞ',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Cancelled]);
    }
}

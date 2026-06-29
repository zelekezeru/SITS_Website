<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ConductStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Resolved = 'resolved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Resolved => 'Resolved',
            self::Archived => 'Archived',
        };
    }

    public function amharicLabel(): string
    {
        return match ($this) {
            self::Draft => 'ረቂቅ',
            self::Submitted => 'ቀርቦ',
            self::UnderReview => 'በስልታዊ ግምገማ ላይ',
            self::Approved => 'ዋቅ ተሰጥቶ',
            self::Rejected => 'ተወገደ',
            self::Resolved => 'ተፈታ',
            self::Archived => 'በማህደር ውስጥ',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Resolved, self::Archived, self::Rejected]);
    }
}

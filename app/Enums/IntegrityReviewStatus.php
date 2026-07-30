<?php

namespace App\Enums;

enum IntegrityReviewStatus: string
{
    case NONE = 'none';
    case UNDER_REVIEW = 'under_review';
    case CLEARED = 'cleared';
    case UPHELD = 'upheld';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Not Reviewed',
            self::UNDER_REVIEW => 'Under Review',
            self::CLEARED => 'Cleared',
            self::UPHELD => 'Upheld',
        };
    }

    /**
     * Valid next states from this one. Enforces the state machine from
     * Phase 7 — a report can't jump straight from none to a decision.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NONE => [self::UNDER_REVIEW],
            self::UNDER_REVIEW => [self::CLEARED, self::UPHELD],
            self::CLEARED, self::UPHELD => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}

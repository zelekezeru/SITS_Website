<?php

namespace App\Services\Integrity;

use App\Models\IntegrityDocument;
use App\Models\User;

/**
 * Per-instructor daily quota, counted against new document analyses
 * created today. Cost-heavier actions (the web-source plagiarism check)
 * are checked against the same daily count with a higher weight rather
 * than a separate ledger — a reasonable approximation given this repo's
 * schema has no dedicated usage-ledger table.
 */
class IntegrityQuota
{
    public function limit(): int
    {
        return (int) config('integrity.daily_quota', 50);
    }

    public function used(User $user): int
    {
        return IntegrityDocument::where('instructor_id', $user->id)
            ->whereDate('created_at', today())
            ->count();
    }

    public function remaining(User $user): int
    {
        return max(0, $this->limit() - $this->used($user));
    }

    public function canConsume(User $user, int $weight = 1): bool
    {
        return ($this->used($user) + $weight) <= $this->limit();
    }
}

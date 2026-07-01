<?php

namespace App\Services;

use App\Models\User;

class LoanPolicy
{
    public int $loanDays;
    public int $maxConcurrent;
    public int $maxRenewals;
    public bool $allowHolds;

    public function __construct(int $loanDays, int $maxConcurrent, int $maxRenewals, bool $allowHolds)
    {
        $this->loanDays = $loanDays;
        $this->maxConcurrent = $maxConcurrent;
        $this->maxRenewals = $maxRenewals;
        $this->allowHolds = $allowHolds;
    }

    public static function for(User $user): self
    {
        $role = $user->primaryRole();

        // Specific configuration as per Phase 6 requirements
        return match ($role?->value) {
            'instructor' => new self(60, 10, 2, true),
            'librarian'  => new self(30, 20, 999, true),
            'student'    => new self(14, 3, 1, true),
            'staff_user' => new self(14, 3, 1, true),
            default      => new self(7, 2, 0, false),
        };
    }
}

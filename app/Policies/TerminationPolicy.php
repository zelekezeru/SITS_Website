<?php

namespace App\Policies;

use App\Models\Termination;
use App\Models\User;

/**
 * Termination Policy
 *
 * Permissions:
 *   - Super Admin only: create/finalize terminations
 */
class TerminationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage employees');
    }

    public function view(User $user, Termination $termination): bool
    {
        if ($user->can('manage employees')) {
            return true;
        }

        return $user->managesDepartment($termination->employee->department_id);
    }

    public function create(User $user): bool
    {
        return $user->can('manage employees');
    }

    public function finalize(User $user, Termination $termination): bool
    {
        return $user->can('manage employees');
    }

    public function delete(User $user, Termination $termination): bool
    {
        return $user->can('manage employees');
    }
}

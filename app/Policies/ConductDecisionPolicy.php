<?php

namespace App\Policies;

use App\Models\ConductDecision;
use App\Models\User;

/**
 * Conduct Decision Policy
 *
 * Permission scoping:
 *   - Super Admin only → can create/update/appeal/overturn decisions
 */
class ConductDecisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage conduct decisions');
    }

    public function view(User $user, ConductDecision $decision): bool
    {
        // Super admin can view all
        if ($user->can('manage conduct decisions')) {
            return true;
        }

        // Department head can view decisions for their department
        if ($user->can('manage department conduct')
            && $user->managesDepartment($decision->conductIssue->employee->department_id)) {
            return true;
        }

        // Employee can view their own decision
        return $decision->conductIssue->employee->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage conduct decisions');
    }

    public function update(User $user, ConductDecision $decision): bool
    {
        return $user->can('manage conduct decisions');
    }

    public function appeal(User $user, ConductDecision $decision): bool
    {
        // Only the affected employee can appeal
        if ($decision->conductIssue->employee->user_id === $user->id) {
            return !$decision->isAppealed() && !$decision->isOverturned();
        }

        return false;
    }

    public function overturn(User $user, ConductDecision $decision): bool
    {
        return $user->can('manage conduct decisions') && !$decision->isOverturned();
    }

    public function restore(User $user, ConductDecision $decision): bool
    {
        return $user->can('manage conduct decisions');
    }

    public function forceDelete(User $user, ConductDecision $decision): bool
    {
        return $user->can('manage conduct decisions');
    }
}

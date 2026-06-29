<?php

namespace App\Policies;

use App\Models\ConductIssue;
use App\Models\User;

/**
 * Conduct Issue Policy
 *
 * Permission scoping:
 *   - Managers/Department Heads → can create issues for employees in their department
 *   - Managers/Department Heads → can view issues for their department
 *   - Super Admin → can create/view/approve/reject all issues
 */
class ConductIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage conduct issues')
            || $user->can('manage department conduct');
    }

    public function view(User $user, ConductIssue $issue): bool
    {
        // Super admin can view all
        if ($user->can('manage conduct issues')) {
            return true;
        }

        // Department head can view issues for their department
        if ($user->can('manage department conduct')
            && $user->managesDepartment($issue->employee->department_id)) {
            return true;
        }

        // Employee can view their own issue
        return $issue->employee->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create conduct issues') || $user->can('manage conduct issues');
    }

    public function update(User $user, ConductIssue $issue): bool
    {
        // Can only update if still in draft status
        if (!$issue->isDraft()) {
            return false;
        }

        // Only the creator can update
        if ($issue->created_by === $user->id) {
            return true;
        }

        // Super admin can update
        return $user->can('manage conduct issues');
    }

    public function delete(User $user, ConductIssue $issue): bool
    {
        // Can only delete if still in draft status
        if (!$issue->isDraft()) {
            return false;
        }

        // Only the creator can delete
        if ($issue->created_by === $user->id) {
            return true;
        }

        // Super admin can delete
        return $user->can('manage conduct issues');
    }

    public function submit(User $user, ConductIssue $issue): bool
    {
        return $this->update($user, $issue);
    }

    public function approve(User $user, ConductIssue $issue): bool
    {
        return $user->can('manage conduct issues');
    }

    public function reject(User $user, ConductIssue $issue): bool
    {
        return $user->can('manage conduct issues');
    }

    public function comment(User $user, ConductIssue $issue): bool
    {
        return $this->view($user, $issue);
    }

    // ----- Predicates ----

    protected function isDraft(ConductIssue $issue): bool
    {
        return $issue->status->value === 'draft';
    }
}

<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

/**
 * Leave Request Policy
 *
 * Permissions:
 *   - Employees: view/create their own leave requests
 *   - Managers: approve/reject leave for their department
 *   - Super Admin: view/approve all leave requests
 */
class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LeaveRequest $leave): bool
    {
        if ($user->can('manage employees')) {
            return true;
        }

        if ($user->managesDepartment($leave->employee->department_id)) {
            return true;
        }

        return $leave->employee->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LeaveRequest $leave): bool
    {
        // Only creator can update if still in draft
        if ($leave->status->value === 'draft') {
            return $leave->created_by === $user->id;
        }

        return $user->can('manage employees');
    }

    public function approve(User $user, LeaveRequest $leave): bool
    {
        return $user->can('manage employees')
            || $user->managesDepartment($leave->employee->department_id);
    }

    public function reject(User $user, LeaveRequest $leave): bool
    {
        return $this->approve($user, $leave);
    }

    public function cancel(User $user, LeaveRequest $leave): bool
    {
        if ($leave->employee->user_id === $user->id) {
            return true;
        }

        return $user->can('manage employees');
    }

    public function delete(User $user, LeaveRequest $leave): bool
    {
        return $user->can('manage employees');
    }
}

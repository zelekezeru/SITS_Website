<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * HR/admin manage all employee records; department managers see their
 * department; everyone may view their own DPF.
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['manage employees', 'view employees', 'view department employees']);
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->canAny(['manage employees', 'view employees'])) {
            return true;
        }

        if ($user->can('view department employees')
            && $user->managesDepartment($employee->department_id)) {
            return true;
        }

        return $employee->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('manage employees');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('manage employees');
    }
}

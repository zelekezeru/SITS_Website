<?php

namespace App\Policies;

use App\Models\EmployeeStatusChange;
use App\Models\User;

class EmployeeStatusChangePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage employees');
    }

    public function view(User $user, EmployeeStatusChange $change): bool
    {
        if ($user->can('manage employees')) {
            return true;
        }

        return $user->managesDepartment($change->employee->department_id);
    }

    public function create(User $user): bool
    {
        return $user->can('manage employees');
    }

    public function update(User $user, EmployeeStatusChange $change): bool
    {
        return $user->can('manage employees');
    }

    public function delete(User $user, EmployeeStatusChange $change): bool
    {
        return $user->can('manage employees');
    }
}

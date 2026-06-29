<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Role-scoping pattern for the whole app:
 *   - Employee  → own tasks (and tasks they collaborate on)
 *   - Head/Manager → tasks in departments they manage ("manage department tasks")
 *   - VP/Admin → all tasks ("manage all tasks")
 * The President / Super Admin bypasses every check via Gate::before.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        // Any authenticated user can see a (controller-scoped) task list.
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task)
            || $this->collaborates($user, $task)
            || $user->can('manage all tasks')
            || ($user->can('manage department tasks') && $this->inManagedDepartment($user, $task));
    }

    public function create(User $user): bool
    {
        return $user->canAny([
            'create tasks',
            'manage own tasks',
            'manage department tasks',
            'manage all tasks',
        ]);
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->can('manage all tasks')) {
            return true;
        }

        if ($user->can('manage department tasks') && $this->inManagedDepartment($user, $task)) {
            return true;
        }

        return $user->can('manage own tasks') && $this->owns($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->can('manage all tasks')) {
            return true;
        }

        if ($user->can('manage department tasks') && $this->inManagedDepartment($user, $task)) {
            return true;
        }

        return $user->can('manage own tasks') && $this->owns($user, $task);
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->can('manage all tasks')
            || ($user->can('manage department tasks') && $this->inManagedDepartment($user, $task));
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->can('manage all tasks');
    }

    public function comment(User $user, Task $task): bool
    {
        return $user->can('comment tasks') && $this->view($user, $task);
    }

    // ----- Predicates ----------------------------------------------------

    protected function owns(User $user, Task $task): bool
    {
        return $task->employee?->user_id === $user->id;
    }

    protected function collaborates(User $user, Task $task): bool
    {
        return $task->collaborators()->whereKey($user->id)->exists();
    }

    protected function inManagedDepartment(User $user, Task $task): bool
    {
        return $user->managesDepartment($task->employee?->department_id);
    }
}

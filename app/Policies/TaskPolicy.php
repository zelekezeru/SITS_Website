<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function belongsToTask(User $user, Task $task)
    {
        return $task->users->contains($user);
    }
}

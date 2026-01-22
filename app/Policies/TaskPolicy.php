<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->project->user_id 
            || $user->id === $task->user_id 
            || $user->id === $task->assigned_user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->project->user_id || $user->id === $task->user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->project->user_id || $user->id === $task->user_id;
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->id === $task->project->user_id || $user->id === $task->user_id;
    }
}

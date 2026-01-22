<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TaskComment $taskComment): bool
    {
        return true;
    }

    public function create(User $user, Task $task): bool
    {
        return $user->id === $task->user_id || $user->id === $task->assigned_user_id;
    }

    public function update(User $user, TaskComment $taskComment): bool
    {
        return $user->id === $taskComment->user_id;
    }

    public function delete(User $user, TaskComment $taskComment): bool
    {
        return $user->id === $taskComment->user_id;
    }
}

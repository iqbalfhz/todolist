<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can change or delete the task.
     */
    public function update(User $user, Task $task): Response
    {
        return $this->allowMembersOf($task->space_id, $user);
    }
}

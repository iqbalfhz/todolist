<?php

namespace App\Policies;

use App\Models\TaskList;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class TaskListPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can change, delete, or add tasks to the list.
     */
    public function update(User $user, TaskList $taskList): Response
    {
        return $this->allowMembersOf($taskList->space_id, $user);
    }
}

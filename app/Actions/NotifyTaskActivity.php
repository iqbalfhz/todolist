<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Task;
use App\Models\User;

class NotifyTaskActivity
{
    /**
     * Tell the given users that the actor made them responsible for the task.
     *
     * @param  list<string>  $assigneeIds
     */
    public function assigned(Task $task, array $assigneeIds, User $actor): void
    {
        foreach (array_diff($assigneeIds, [$actor->id]) as $assigneeId) {
            SendPushNotification::dispatch(
                $assigneeId,
                PushType::TaskAssigned,
                $task->title,
                __('push.task_assigned.body', ['name' => $actor->name]),
                $task->space_id,
                taskId: $task->id,
            )->afterCommit();
        }
    }

    /**
     * Tell the creator of the task that someone else completed it.
     */
    public function completed(Task $task, User $actor): void
    {
        if ($task->created_by === $actor->id) {
            return;
        }

        SendPushNotification::dispatch(
            $task->created_by,
            PushType::TaskCompleted,
            $task->title,
            __('push.task_completed.body', ['name' => $actor->name]),
            $task->space_id,
            taskId: $task->id,
        )->afterCommit();
    }
}

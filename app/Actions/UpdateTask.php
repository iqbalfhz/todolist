<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use App\Rules\Iso8601WithOffset;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTask
{
    public function __construct(
        private FindWritableTaskList $findWritableTaskList,
        private ResolveAssignees $resolveAssignees,
        private CalculateRemindAt $calculateRemindAt,
        private NotifyTaskActivity $notifyTaskActivity,
    ) {}

    /**
     * Apply the given changes to the task.
     *
     * @param  array<string, mixed>  $changes
     */
    public function handle(User $actor, Task $task, array $changes): Task
    {
        return DB::transaction(function () use ($actor, $task, $changes): Task {
            $wasCompleted = $task->completed_at !== null;

            $this->moveToList($task, $changes);
            $this->changeDueDate($task, $changes);
            $this->changeCompletion($task, $changes, $actor);

            $task->fill(Arr::only($changes, ['title', 'notes', 'priority', 'position']));
            $task->updated_by = $actor->id;

            $newAssigneeIds = $this->changeAssignees($task, $changes);

            $task->save();

            $this->notifyTaskActivity->assigned($task, $newAssigneeIds, $actor);

            if (! $wasCompleted && $task->completed_at !== null) {
                $this->notifyTaskActivity->completed($task, $actor);
            }

            return $task;
        });
    }

    /**
     * Move the task to another list, which must be an active list in the same space.
     *
     * @param  array<string, mixed>  $changes
     */
    private function moveToList(Task $task, array $changes): void
    {
        if (! isset($changes['task_list_id']) || $changes['task_list_id'] === $task->task_list_id) {
            return;
        }

        $list = $this->findWritableTaskList->handle($changes['task_list_id']);

        if ($list->space_id !== $task->space_id) {
            throw ValidationException::withMessages([
                'task_list_id' => __('api.validation.different_space'),
            ]);
        }

        $task->task_list_id = $list->id;
    }

    /**
     * Change the due date and time, then recalculate the reminder and let it fire again.
     *
     * @param  array<string, mixed>  $changes
     */
    private function changeDueDate(Task $task, array $changes): void
    {
        $dueDate = array_key_exists('due_date', $changes) ? $changes['due_date'] : $task->due_date;
        $dueTime = array_key_exists('due_time', $changes) ? $changes['due_time'] : $this->hoursAndMinutes($task->due_time);

        if ($dueDate === null && array_key_exists('due_date', $changes)) {
            $dueTime = null;
        }

        if ($dueTime !== null && $dueDate === null) {
            throw ValidationException::withMessages([
                'due_time' => __('api.validation.due_time_needs_due_date'),
            ]);
        }

        if ($dueDate === $task->due_date && $dueTime === $this->hoursAndMinutes($task->due_time)) {
            return;
        }

        $task->due_date = $dueDate;
        $task->due_time = $dueTime;
        $task->remind_at = $this->calculateRemindAt->handle($dueDate, $dueTime, $task->space->timezone);
        $task->reminder_sent_at = null;
    }

    /**
     * Mark the task completed by the actor, or clear the completion.
     *
     * @param  array<string, mixed>  $changes
     */
    private function changeCompletion(Task $task, array $changes, User $actor): void
    {
        if (! array_key_exists('completed_at', $changes)) {
            return;
        }

        if ($changes['completed_at'] === null) {
            $task->completed_at = null;
            $task->completed_by = null;

            return;
        }

        $task->completed_by ??= $actor->id;
        $task->completed_at = Iso8601WithOffset::toServerTime($changes['completed_at']);
    }

    /**
     * Replace the assignees and return the IDs of the users who were added.
     *
     * The pivot table has no cursor of its own, so the task is always saved
     * with a new updated_at when its assignees change.
     *
     * @param  array<string, mixed>  $changes
     * @return list<string>
     */
    private function changeAssignees(Task $task, array $changes): array
    {
        if (! isset($changes['assignee_ids'])) {
            return [];
        }

        $assigneeIds = $this->resolveAssignees->handle($changes['assignee_ids'], $task->space_id);
        $synced = $task->assignees()->sync($assigneeIds);

        if ($synced['attached'] !== [] || $synced['detached'] !== []) {
            $task->updated_at = $task->freshTimestamp();
            $task->unsetRelation('assignees');
        }

        return array_values($synced['attached']);
    }

    /**
     * Cut a stored time such as 17:00:00 down to the 17:00 the app sends.
     */
    private function hoursAndMinutes(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}

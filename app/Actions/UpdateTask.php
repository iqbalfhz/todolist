<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\Task;
use App\Models\TaskList;
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
        private CheckTaskRepeat $checkTaskRepeat,
        private CreateNextTask $createNextTask,
    ) {}

    /**
     * Apply the given changes to the task.
     *
     * The task row is locked first, so two phones completing the same
     * recurring task at once create its next occurrence only once.
     *
     * @param  array<string, mixed>  $changes
     */
    public function handle(User $actor, Task $task, array $changes): Task
    {
        return DB::transaction(function () use ($actor, $task, $changes): Task {
            Task::withTrashed()->whereKey($task->id)->lockForUpdate()->first();
            $task->refresh();

            if ($task->trashed()) {
                throw new ApiException(ErrorCode::ResourceDeleted);
            }

            $wasCompleted = $task->completed_at !== null;

            $this->moveToList($task, $changes, $actor);
            $this->changeDueDate($task, $changes);
            $this->changeRepeat($task, $changes);
            $this->changeCompletion($task, $changes, $actor);

            $task->fill(Arr::only($changes, ['title', 'notes', 'priority', 'position']));
            $task->updated_by = $actor->id;

            $newAssigneeIds = $this->changeAssignees($task, $changes);

            $this->moveOnFromCompletion($task, $wasCompleted, $actor);

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
    private function moveToList(Task $task, array $changes, User $actor): void
    {
        if (! isset($changes['task_list_id']) || $changes['task_list_id'] === $task->task_list_id) {
            return;
        }

        $list = $this->findWritableTaskList->handle($actor, $changes['task_list_id']);

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
     * Change the repeat rule, checked together with the due date and the list the task ends up with.
     *
     * Clearing the due date clears the rule too. A new due date or rule
     * makes the due date the anchor the next occurrences are counted from;
     * otherwise the anchor stays, so a month-end date keeps its day.
     *
     * @param  array<string, mixed>  $changes
     */
    private function changeRepeat(Task $task, array $changes): void
    {
        $frequencySent = array_key_exists('repeat_frequency', $changes);
        $frequency = $frequencySent ? $changes['repeat_frequency'] : $task->repeat_frequency?->value;

        if (! $frequencySent && $task->due_date === null) {
            $frequency = null;
        }

        $interval = null;

        if ($frequency !== null) {
            $this->checkTaskRepeat->ensureAllowed(
                $task->due_date,
                TaskList::query()->whereKey($task->task_list_id)->value('trip_id'),
                $frequencySent ? 'repeat_frequency' : 'task_list_id',
            );

            $interval = (int) (($changes['repeat_interval'] ?? null) ?? $task->repeat_interval ?? 1);
        }

        $ruleChanged = $frequency !== $task->repeat_frequency?->value || $interval !== $task->repeat_interval;

        $task->repeat_frequency = $frequency;
        $task->repeat_interval = $interval;

        if ($frequency === null) {
            $task->repeat_anchor = null;
        } elseif ($ruleChanged || $task->isDirty('due_date') || $task->repeat_anchor === null) {
            $task->repeat_anchor = $task->due_date;
        }
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
     * Create the next occurrence of a recurring task that was just completed,
     * or take it back when the completion is undone.
     *
     * A task creates at most one next occurrence. Undoing removes it only
     * while nobody has touched it, so completing again cannot make a second.
     */
    private function moveOnFromCompletion(Task $task, bool $wasCompleted, User $actor): void
    {
        $isCompleted = $task->completed_at !== null;

        if (! $wasCompleted && $isCompleted && $task->repeat_frequency !== null && $task->next_task_id === null) {
            $task->next_task_id = $this->createNextTask->handle($task, $actor)->id;

            return;
        }

        if ($wasCompleted && ! $isCompleted && $task->next_task_id !== null && $this->removeUntouched($task->next_task_id)) {
            $task->next_task_id = null;
        }
    }

    /**
     * Delete the next occurrence if nobody changed, completed, commented on,
     * or reacted to it since it was created.
     *
     * One that was already deleted stays linked, so deleting it keeps the
     * series stopped.
     */
    private function removeUntouched(string $nextTaskId): bool
    {
        $next = Task::query()->whereKey($nextTaskId)->lockForUpdate()->first();

        if ($next === null || $next->completed_at !== null || ! $next->updated_at->eq($next->created_at) || $this->hasDiscussion($next)) {
            return false;
        }

        $next->delete();

        return true;
    }

    /**
     * Determine whether anyone commented on or reacted to the task.
     *
     * The reads lock, so they also see a comment or reaction stored after
     * this transaction took its snapshot; storing one holds the task's row,
     * which this transaction has locked by now.
     */
    private function hasDiscussion(Task $task): bool
    {
        return $task->comments()->sharedLock()->exists() || $task->reactions()->sharedLock()->exists();
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

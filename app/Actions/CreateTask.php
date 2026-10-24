<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use App\Rules\Iso8601WithOffset;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateTask
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
     * Create a task with the ID the phone chose.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): Task
    {
        return DB::transaction(function () use ($actor, $id, $data): Task {
            $list = $this->findWritableTaskList->handle($actor, $data['task_list_id']);
            $assigneeIds = $this->resolveAssignees->handle($data['assignee_ids'] ?? [$actor->id], $list->space_id);

            $task = new Task(array_filter(
                Arr::only($data, ['title', 'notes', 'due_date', 'due_time', 'priority', 'position']),
                fn (mixed $value): bool => $value !== null,
            ));
            $task->id = $id;
            $task->task_list_id = $list->id;
            $task->space_id = $list->space_id;
            $task->created_by = $actor->id;
            $task->updated_by = $actor->id;
            $task->remind_at = $this->calculateRemindAt->handle($task->due_date, $task->due_time, $list->space->timezone);

            if (isset($data['repeat_frequency'])) {
                $this->checkTaskRepeat->ensureAllowed($task->due_date, $list->trip_id, 'repeat_frequency');

                $task->repeat_frequency = $data['repeat_frequency'];
                $task->repeat_interval = $data['repeat_interval'] ?? 1;
                $task->repeat_anchor = $task->due_date;
            }

            if (isset($data['completed_at'])) {
                $task->completed_at = Iso8601WithOffset::toServerTime($data['completed_at']);
                $task->completed_by = $actor->id;
            }

            $task->save();
            $task->assignees()->sync($assigneeIds);

            // A recurring task that arrives already completed moves on to its next occurrence at once.
            if ($task->completed_at !== null && $task->repeat_frequency !== null) {
                $task->next_task_id = $this->createNextTask->handle($task, $actor)->id;
                $task->save();
            }

            $this->notifyTaskActivity->assigned($task, $assigneeIds, $actor);

            return $task;
        });
    }
}

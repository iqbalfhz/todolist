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
    ) {}

    /**
     * Create a task with the ID the phone chose.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): Task
    {
        return DB::transaction(function () use ($actor, $id, $data): Task {
            $list = $this->findWritableTaskList->handle($data['task_list_id']);
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

            if (isset($data['completed_at'])) {
                $task->completed_at = Iso8601WithOffset::toServerTime($data['completed_at']);
                $task->completed_by = $actor->id;
            }

            $task->save();
            $task->assignees()->sync($assigneeIds);

            $this->notifyTaskActivity->assigned($task, $assigneeIds, $actor);

            return $task;
        });
    }
}

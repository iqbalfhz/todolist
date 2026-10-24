<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;

class CreateNextTask
{
    public function __construct(
        private CalculateNextDueDate $calculateNextDueDate,
        private CalculateRemindAt $calculateRemindAt,
    ) {}

    /**
     * Create the next occurrence of a recurring task that was just completed.
     *
     * It is due on the first occurrence after the later of the old due date
     * and the day of completion, so missed occurrences do not pile up. It
     * stays in the same list with the content, assignees, and rule of the
     * task. The creator stays the same, so the completion push of the next
     * one still reaches them, and no assignment push is sent for it.
     */
    public function handle(Task $task, User $actor): Task
    {
        $timezone = $task->space->timezone;
        $completedOn = CarbonImmutable::instance($task->completed_at)->setTimezone($timezone)->toDateString();

        $next = new Task([
            'title' => $task->title,
            'notes' => $task->notes,
            'priority' => $task->priority,
            'position' => $task->position,
            'due_time' => $task->due_time,
        ]);
        $next->task_list_id = $task->task_list_id;
        $next->space_id = $task->space_id;
        $next->due_date = $this->calculateNextDueDate->handle(
            $task->repeat_frequency,
            $task->repeat_interval,
            $task->repeat_anchor ?? $task->due_date,
            max($task->due_date, $completedOn),
        );
        $next->repeat_frequency = $task->repeat_frequency;
        $next->repeat_interval = $task->repeat_interval;
        $next->repeat_anchor = $task->repeat_anchor;
        $next->created_by = $task->created_by;
        $next->updated_by = $actor->id;
        $next->remind_at = $this->calculateRemindAt->handle($next->due_date, $next->due_time, $timezone);
        $next->save();
        $next->assignees()->sync($task->assignees()->pluck('users.id')->all());

        return $next;
    }
}

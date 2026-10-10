<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Task;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SendDueReminders
{
    /**
     * How late a reminder may still be sent, for example after the server was down.
     */
    public const GRACE_MINUTES = 30;

    /**
     * Push a reminder to the assignees of every task whose remind_at has arrived.
     *
     * @return array{sent: int, late: int} The reminders sent, and those marked as sent because they were too late.
     */
    public function handle(): array
    {
        $now = CarbonImmutable::now();
        $late = $this->skipLateReminders($now);
        $sent = 0;

        $this->dueReminders($now)->with('assignees:id')->lazyById(200)->each(function (Task $task) use ($now, &$sent): void {
            // The claim and the queued pushes are saved together, so a run
            // that is cut short leaves the reminder for the next minute.
            DB::transaction(function () use ($task, $now, &$sent): void {
                if (! $this->claim($task, $now)) {
                    return;
                }

                foreach ($task->assignees as $assignee) {
                    SendPushNotification::dispatch(
                        $assignee->id,
                        PushType::TaskReminder,
                        $task->title,
                        $this->body($task),
                        $task->space_id,
                        taskId: $task->id,
                    );
                }

                $sent++;
            });
        });

        return ['sent' => $sent, 'late' => $late];
    }

    /**
     * Mark every reminder that is past the grace period as sent, without a push.
     *
     * This covers completed and deleted tasks and tasks in archived lists
     * too: by now their reminder would be too late anyway, and marking them
     * keeps the set of open reminders small.
     */
    private function skipLateReminders(CarbonImmutable $now): int
    {
        return Task::withoutTimestamps(fn (): int => Task::withTrashed()
            ->whereNull('reminder_sent_at')
            ->where('remind_at', '<', $this->format($now->subMinutes(self::GRACE_MINUTES)))
            ->update(['reminder_sent_at' => $this->format($now)]));
    }

    /**
     * Get the open reminders that have arrived, for tasks that are still to be done.
     *
     * A completed task or a task in an archived list keeps its reminder open,
     * so it is still sent if the task comes back within the grace period.
     *
     * @return Builder<Task>
     */
    private function dueReminders(CarbonImmutable $now): Builder
    {
        return Task::query()
            ->whereNull('reminder_sent_at')
            ->whereBetween('remind_at', [
                $this->format($now->subMinutes(self::GRACE_MINUTES)),
                $this->format($now),
            ])
            ->whereNull('completed_at')
            ->whereHas('taskList', fn (Builder $list) => $list->active());
    }

    /**
     * Mark the reminder as sent if it is still open and due.
     *
     * The update repeats the conditions of the reminder, so a task that was
     * rescheduled, completed, or claimed by another run in the meantime is
     * left alone. It does not touch updated_at, so it starts no sync.
     */
    private function claim(Task $task, CarbonImmutable $now): bool
    {
        return Task::withoutTimestamps(fn (): int => $this->dueReminders($now)
            ->whereKey($task->id)
            ->where('remind_at', $this->format($task->remind_at))
            ->update(['reminder_sent_at' => $this->format($now)])) === 1;
    }

    /**
     * Get the text of the reminder, with the due time when the task has one.
     */
    private function body(Task $task): string
    {
        if ($task->due_time === null) {
            return __('push.task_reminder.without_time');
        }

        return __('push.task_reminder.with_time', [
            'time' => str_replace(':', '.', substr($task->due_time, 0, 5)),
        ]);
    }

    /**
     * Write a time the way the tasks table stores it, microseconds included.
     */
    private function format(DateTimeInterface $time): string
    {
        return $time->format((new Task)->getDateFormat());
    }
}

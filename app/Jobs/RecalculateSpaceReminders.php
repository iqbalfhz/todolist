<?php

namespace App\Jobs;

use App\Actions\CalculateRemindAt;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateSpaceReminders implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $spaceId) {}

    /**
     * Recalculate when each task in the space is reminded, after its timezone changed.
     */
    public function handle(CalculateRemindAt $calculateRemindAt): void
    {
        $space = Space::query()->find($this->spaceId);

        if ($space === null) {
            return;
        }

        $space->tasks()->whereNotNull('due_date')->chunkById(200, function (Collection $tasks) use ($calculateRemindAt, $space): void {
            $tasks->each(fn (Task $task) => $task->update([
                'remind_at' => $calculateRemindAt->handle($task->due_date, $task->due_time, $space->timezone),
            ]));
        });
    }
}

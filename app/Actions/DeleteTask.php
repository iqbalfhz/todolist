<?php

namespace App\Actions;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class DeleteTask
{
    public function __construct(private DeleteDiscussions $deleteDiscussions) {}

    /**
     * Soft delete the task together with its comments and reactions.
     */
    public function handle(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            $task->delete();

            $this->deleteDiscussions->handle(Task::query()->whereKey($task->id));
        });
    }
}

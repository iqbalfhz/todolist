<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Support\Facades\DB;

class DeleteTaskList
{
    public function __construct(
        private KeepOneActiveList $keepOneActiveList,
        private DeleteDiscussions $deleteDiscussions,
    ) {}

    /**
     * Soft delete the list together with its tasks and their comments and reactions.
     *
     * The tasks are deleted through Eloquent, which also bumps their
     * updated_at, so the deletions reach the other phone through sync. The
     * checklist of a trip only goes away together with its trip.
     */
    public function handle(TaskList $list): void
    {
        if ($list->trip_id !== null) {
            throw new ApiException(ErrorCode::TripChecklist);
        }

        DB::transaction(function () use ($list): void {
            $this->keepOneActiveList->handle($list);

            // Wait for tasks that are being written to this list, so none is left behind undeleted.
            TaskList::query()->whereKey($list->id)->lockForUpdate()->first();

            $list->tasks()->delete();
            $this->deleteDiscussions->handle(Task::query()->where('task_list_id', $list->id));
            $list->delete();
        });
    }
}

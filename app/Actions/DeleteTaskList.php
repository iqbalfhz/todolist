<?php

namespace App\Actions;

use App\Models\TaskList;
use Illuminate\Support\Facades\DB;

class DeleteTaskList
{
    public function __construct(private KeepOneActiveList $keepOneActiveList) {}

    /**
     * Soft delete the list together with its tasks.
     *
     * The tasks are deleted through Eloquent, which also bumps their
     * updated_at, so the deletions reach the other phone through sync.
     */
    public function handle(TaskList $list): void
    {
        DB::transaction(function () use ($list): void {
            $this->keepOneActiveList->handle($list);

            // Wait for tasks that are being written to this list, so none is left behind undeleted.
            TaskList::query()->whereKey($list->id)->lockForUpdate()->first();

            $list->tasks()->delete();
            $list->delete();
        });
    }
}

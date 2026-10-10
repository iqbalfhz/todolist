<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class FindWritableTaskList
{
    /**
     * Find the list a task is being created in or moved to.
     *
     * Answers 404 when the actor cannot reach the list, and 409 when it was
     * deleted or archived. Inside a transaction the list stays share-locked,
     * so it cannot be deleted before the task is written.
     */
    public function handle(User $actor, string $id): TaskList
    {
        $list = TaskList::withTrashed()->whereKey($id)->sharedLock()->first();

        if ($list === null) {
            throw (new ModelNotFoundException)->setModel(TaskList::class, [$id]);
        }

        Gate::forUser($actor)->authorize('update', $list);

        if ($list->trashed()) {
            throw new ApiException(ErrorCode::ParentDeleted, __('api.parent_deleted.task_list'));
        }

        if ($list->archived_at !== null) {
            throw new ApiException(ErrorCode::ParentArchived);
        }

        return $list;
    }
}

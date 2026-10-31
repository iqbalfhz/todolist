<?php

namespace App\Actions;

use App\Jobs\DeleteStoredDirectory;
use App\Models\MemoryPhoto;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\Task;

class PurgeSpaceContent
{
    /**
     * Delete everything the space holds for good, the rows deleted earlier
     * included, and remove the photo files once that is saved.
     *
     * The space itself stays, for the caller to keep or delete. Call it
     * inside a transaction that locked the space.
     */
    public function handle(string $spaceId): void
    {
        // A task points at the task that followed it, so those links go first.
        Task::withTrashed()->where('space_id', $spaceId)->update(['next_task_id' => null]);

        foreach (Space::CONTENT_MODELS as $model) {
            $model::withTrashed()->where('space_id', $spaceId)->forceDelete();
        }

        SpaceInvitation::query()->where('space_id', $spaceId)->update(['space_id' => null]);

        DeleteStoredDirectory::dispatch(MemoryPhoto::DISK, MemoryPhoto::directoryFor($spaceId))->afterCommit();
    }
}

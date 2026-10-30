<?php

namespace App\Actions;

use App\Enums\SpaceType;
use App\Jobs\DeleteStoredDirectory;
use App\Models\MemoryPhoto;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PurgeClosedSpaces
{
    /**
     * Delete for good every shared space that was closed more than
     * Space::RESTORABLE_DAYS ago, with everything it held and its photos.
     *
     * @return int The spaces deleted.
     */
    public function handle(): int
    {
        $cutoff = now()->subDays(Space::RESTORABLE_DAYS);

        return Space::onlyTrashed()
            ->where('type', SpaceType::Shared)
            ->where('deleted_at', '<', $cutoff)
            ->pluck('id')
            ->filter(fn (string $spaceId): bool => $this->purge($spaceId, $cutoff))
            ->count();
    }

    /**
     * Delete one closed space for good, unless the partners got it back in the meantime.
     *
     * Every row is deleted from the database, the ones deleted earlier
     * included, and the photo files are removed from R2 once that is saved.
     */
    private function purge(string $spaceId, CarbonInterface $cutoff): bool
    {
        return DB::transaction(function () use ($spaceId, $cutoff): bool {
            // The lock makes a restore that is under way finish first, and the
            // conditions are checked again under it.
            $space = Space::onlyTrashed()
                ->whereKey($spaceId)
                ->where('type', SpaceType::Shared)
                ->where('deleted_at', '<', $cutoff)
                ->lockForUpdate()
                ->first();

            if ($space === null) {
                return false;
            }

            // A task points at the task that followed it, so those links go first.
            Task::withTrashed()->where('space_id', $spaceId)->update(['next_task_id' => null]);

            foreach (Space::CONTENT_MODELS as $model) {
                $model::withTrashed()->where('space_id', $spaceId)->forceDelete();
            }

            SpaceInvitation::query()->where('space_id', $spaceId)->update(['space_id' => null]);

            $space->forceDelete();

            DeleteStoredDirectory::dispatch(MemoryPhoto::DISK, MemoryPhoto::directoryFor($spaceId))->afterCommit();

            return true;
        });
    }
}

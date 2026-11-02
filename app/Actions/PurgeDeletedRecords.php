<?php

namespace App\Actions;

use App\Jobs\DeleteStoredFiles;
use App\Models\DailyReminder;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurgeDeletedRecords
{
    /**
     * How many days a deleted row is kept before it is deleted for good.
     *
     * Every phone that syncs within that time learns of the deletion
     * through since; a phone that waits longer rebuilds its cache instead
     * (see BuildSyncPage::SINCE_LIMIT_DAYS).
     */
    public const RETENTION_DAYS = 30;

    /**
     * The foreign keys between synced tables that keep a deleted row while
     * another row, deleted or not, still points at it, by the model they
     * point at: [model that points, column].
     *
     * tasks.next_task_id is the only one left out, because an active task
     * may point at a deleted occurrence for good; that link is cleared instead.
     *
     * @var array<class-string<Model>, list<array{0: class-string<Model>, 1: string}>>
     */
    public const BLOCKING_LINKS = [
        TaskList::class => [[Task::class, 'task_list_id']],
        Trip::class => [[TaskList::class, 'trip_id'], [Memory::class, 'trip_id']],
        SavingsGoal::class => [[SavingsEntry::class, 'savings_goal_id'], [Trip::class, 'savings_goal_id']],
        Memory::class => [[MemoryPhoto::class, 'memory_id']],
    ];

    /**
     * The most rows deleted in one transaction, so a large purge holds its locks briefly.
     */
    private const CHUNK_SIZE = 500;

    /**
     * Delete for good the rows deleted more than RETENTION_DAYS ago, and the
     * records the server kept for itself that are no longer needed.
     *
     * Space::CONTENT_MODELS lists a model before the one its rows belong to,
     * so a row and its parent that were deleted together go in the same run.
     *
     * @return array{records: int, reminders: int, invitations: int, cache: int}
     */
    public function handle(): array
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS);

        return [
            'records' => collect(Space::CONTENT_MODELS)->sum(fn (string $model): int => $this->purge($model, $cutoff)),
            'reminders' => DailyReminder::query()->where('created_at', '<', $cutoff)->delete(),
            'invitations' => SpaceInvitation::query()->where('expires_at', '<', $cutoff)->delete(),
            'cache' => $this->deleteExpiredCache(),
        ];
    }

    /**
     * Delete the purgeable rows of one model, a chunk at a time.
     *
     * A row that something started pointing at in the meantime is neither
     * deleted nor picked again, so the loop always ends.
     *
     * @param  class-string<Model>  $model
     */
    private function purge(string $model, CarbonInterface $cutoff): int
    {
        $purged = 0;

        while (($ids = $this->purgeable($model, $cutoff)->limit(self::CHUNK_SIZE)->pluck('id'))->isNotEmpty()) {
            $purged += DB::transaction(fn (): int => $this->purgeChunk($model, $ids, $cutoff));
        }

        return $purged;
    }

    /**
     * Delete one chunk of rows, after what has to change before they go.
     *
     * @param  class-string<Model>  $model
     * @param  Collection<int, string>  $ids
     */
    private function purgeChunk(string $model, Collection $ids, CarbonInterface $cutoff): int
    {
        if ($model === Task::class) {
            // The update sets a new updated_at, so the phones receive the cleared link too.
            Task::withTrashed()->whereIn('next_task_id', $ids)->update(['next_task_id' => null]);
        }

        $paths = $model === MemoryPhoto::class ? $this->photoPaths($ids) : [];

        $deleted = $this->purgeable($model, $cutoff)->whereKey($ids)->forceDelete();

        if ($paths !== []) {
            DeleteStoredFiles::dispatch(MemoryPhoto::DISK, $paths)->afterCommit();
        }

        return $deleted;
    }

    /**
     * Get the deleted rows of a model that are past the retention and that no row points at anymore.
     *
     * @param  class-string<Model>  $model
     */
    private function purgeable(string $model, CarbonInterface $cutoff): Builder
    {
        $query = $model::onlyTrashed();
        $table = $query->getModel()->getTable();

        $query->where($query->getModel()->getQualifiedDeletedAtColumn(), '<', $cutoff);

        foreach (self::BLOCKING_LINKS[$model] ?? [] as [$pointingModel, $column]) {
            $pointingTable = (new $pointingModel)->getTable();

            $query->whereNotExists(fn (QueryBuilder $links) => $links
                ->selectRaw('1')
                ->from($pointingTable)
                ->whereColumn("{$pointingTable}.{$column}", "{$table}.id"));
        }

        return $query;
    }

    /**
     * Get the files of the photos about to be purged.
     *
     * They were removed when the photos were deleted, so removing them
     * again only catches a removal that failed back then.
     *
     * @param  Collection<int, string>  $ids
     * @return list<string>
     */
    private function photoPaths(Collection $ids): array
    {
        return MemoryPhoto::onlyTrashed()
            ->whereKey($ids)
            ->get(['path', 'thumb_path'])
            ->flatMap(fn (MemoryPhoto $photo): array => [$photo->path, $photo->thumb_path])
            ->values()
            ->all();
    }

    /**
     * Delete the cache entries that already expired, when the cache lives in the database.
     *
     * The database store removes an expired entry only when its key is read
     * again, so entries nobody asks for again would stay behind.
     */
    private function deleteExpiredCache(): int
    {
        $store = config('cache.stores.'.config('cache.default'));

        if (($store['driver'] ?? null) !== 'database') {
            return 0;
        }

        return DB::connection($store['connection'] ?? null)
            ->table($store['table'] ?? 'cache')
            ->where('expiration', '<=', now()->getTimestamp())
            ->delete();
    }
}

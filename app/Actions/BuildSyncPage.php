<?php

namespace App\Actions;

use App\Models\User;
use App\Rules\SyncCursor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BuildSyncPage
{
    /**
     * The most rows of one collection that a page carries.
     */
    public const PAGE_SIZE = 500;

    /**
     * How far a sync reaches back before the time the phone last synced, so a
     * change that was still being saved at that moment is not missed.
     */
    public const REWIND_SECONDS = 5;

    public function __construct(private QuerySyncCollection $querySyncCollection) {}

    /**
     * Collect one page of the rows in the user's spaces.
     *
     * Without a cursor a sync starts: everything active when $since is null,
     * otherwise everything changed since then, deleted rows included. A
     * cursor continues the sync it came from, and $since is not read.
     *
     * @param  array{server_time: string, with_deleted: bool, positions: array<string, array{0: string, 1: string}|null>}|null  $cursor
     * @return array{changes: array<string, EloquentCollection<int, Model>>, server_time: CarbonImmutable, next_cursor: string|null}
     */
    public function handle(User $user, ?CarbonImmutable $since = null, ?array $cursor = null): array
    {
        $state = $cursor ?? $this->start($since);
        $openSpaceIds = $this->spaceIds($user, withClosed: false);
        $spaceIds = $state['with_deleted'] ? $this->spaceIds($user, withClosed: true) : $openSpaceIds;
        $changes = [];
        $hasMore = false;

        foreach (SyncCursor::COLLECTIONS as $collection) {
            // A closed shared space reaches the phone once more, with deleted_at,
            // so the phone removes it. What it held is never sent again.
            $collectionSpaceIds = $collection === 'spaces' ? $spaceIds : $openSpaceIds;

            $rows = $this->query($collection, $collectionSpaceIds, $state['with_deleted'], $state['positions'][$collection])
                ->limit(self::PAGE_SIZE + 1)
                ->get();

            if ($rows->count() > self::PAGE_SIZE) {
                $hasMore = true;
                $rows = $rows->take(self::PAGE_SIZE);
            }

            if ($rows->isNotEmpty()) {
                $state['positions'][$collection] = [
                    $rows->last()->updated_at->format(SyncCursor::TIME_FORMAT),
                    $rows->last()->id,
                ];
            }

            $changes[$collection] = $rows;
        }

        return [
            'changes' => $changes,
            'server_time' => CarbonImmutable::createFromFormat(SyncCursor::TIME_FORMAT, $state['server_time'], 'UTC'),
            'next_cursor' => $hasMore ? SyncCursor::encode($state) : null,
        ];
    }

    /**
     * Get the state a sync starts from.
     *
     * The server time is taken before any row is read, so a change saved
     * while the pages are fetched falls inside the next sync. The rewind is
     * applied here only, which keeps it to once per sync.
     *
     * @return array{server_time: string, with_deleted: bool, positions: array<string, array{0: string, 1: string}|null>}
     */
    private function start(?CarbonImmutable $since): array
    {
        // No row is older than the Unix epoch, and MySQL rejects a time before the year 0000.
        $from = $since?->utc()
            ->subSeconds(self::REWIND_SECONDS)
            ->max(CarbonImmutable::createFromTimestampUTC(0))
            ->format(SyncCursor::TIME_FORMAT);

        return [
            'server_time' => now()->format(SyncCursor::TIME_FORMAT),
            'with_deleted' => $since !== null,
            'positions' => array_fill_keys(SyncCursor::COLLECTIONS, $from === null ? null : [$from, '']),
        ];
    }

    /**
     * Get the IDs of the spaces the user is a member of, and of the shared spaces they closed when asked.
     *
     * The partner's personal space is never among them, which is what keeps
     * its content out of every collection.
     *
     * @return Collection<int, string>
     */
    private function spaceIds(User $user, bool $withClosed): Collection
    {
        $spaces = $user->spaces();

        if ($withClosed) {
            $spaces->withTrashed();
        }

        return $spaces->pluck('spaces.id');
    }

    /**
     * Get the rows of a collection that come after the given position, ordered by (updated_at, id).
     *
     * Comparing both columns lets rows that share one updated_at continue on
     * the next page without being repeated or skipped. An empty ID in the
     * position sorts before every row, so it includes the whole of that moment.
     *
     * @param  Collection<int, string>  $spaceIds
     * @param  array{0: string, 1: string}|null  $position
     * @return Builder<Model>
     */
    private function query(string $collection, Collection $spaceIds, bool $withDeleted, ?array $position): Builder
    {
        $query = $this->querySyncCollection->handle($collection, $spaceIds);

        if ($withDeleted) {
            $query->withTrashed();
        }

        if ($position !== null) {
            [$updatedAt, $id] = $position;

            $query->where(fn (Builder $query) => $query
                ->where('updated_at', '>', $updatedAt)
                ->orWhere(fn (Builder $query) => $query->where('updated_at', $updatedAt)->where('id', '>', $id)));
        }

        return $query->orderBy('updated_at')->orderBy('id');
    }
}

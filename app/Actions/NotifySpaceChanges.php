<?php

namespace App\Actions;

use App\Enums\SpaceType;
use App\Jobs\SendSyncPush;
use App\Models\Space;
use App\Models\User;

/**
 * Gathers the spaces whose synced data changed during one request, then
 * tells the other members' phones once the request is done.
 *
 * Bound as a scoped instance, so each request starts empty.
 */
class NotifySpaceChanges
{
    /**
     * The IDs of the spaces that changed, as keys.
     *
     * @var array<string, true>
     */
    private array $spaceIds = [];

    /**
     * Start gathering for a new request, forgetting changes made before it.
     */
    public function start(): void
    {
        $this->spaceIds = [];
    }

    /**
     * Note that data in the space changed.
     */
    public function record(string $spaceId): void
    {
        $this->spaceIds[$spaceId] = true;
    }

    /**
     * Queue a sync push for every member of a changed shared space, except the one who made the change.
     */
    public function send(?User $actor): void
    {
        if ($this->spaceIds === []) {
            return;
        }

        $spaceIds = array_keys($this->spaceIds);
        $this->spaceIds = [];

        Space::query()
            ->whereKey($spaceIds)
            ->where('type', SpaceType::Shared)
            ->with('members')
            ->get()
            ->each(fn (Space $space) => $space->members
                ->reject(fn (User $member): bool => $member->is($actor))
                ->each(fn (User $member) => SendSyncPush::queueFor($member->id, $space->id)));
    }
}

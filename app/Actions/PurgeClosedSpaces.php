<?php

namespace App\Actions;

use App\Enums\SpaceType;
use App\Models\Space;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PurgeClosedSpaces
{
    public function __construct(private PurgeSpaceContent $purgeSpaceContent) {}

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

            $this->purgeSpaceContent->handle($spaceId);

            $space->forceDelete();

            return true;
        });
    }
}

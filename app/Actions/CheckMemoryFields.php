<?php

namespace App\Actions;

use App\Models\Trip;
use Illuminate\Validation\ValidationException;

class CheckMemoryFields
{
    /**
     * Get the trip ID to store for a memory in the given space.
     *
     * A trip that was deleted in the meantime, usually by the partner, is
     * stored as no link, so the rest of the change still goes through. Inside
     * a transaction the trip stays share-locked, so a deletion that unlinks
     * its memories waits until the memory is saved.
     */
    public function linkableTripId(string $spaceId, ?string $tripId): ?string
    {
        if ($tripId === null) {
            return null;
        }

        $trip = Trip::withTrashed()->whereKey($tripId)->sharedLock()->first();

        if ($trip === null || $trip->space_id !== $spaceId) {
            throw ValidationException::withMessages(['trip_id' => __('api.validation.memory_trip_different_space')]);
        }

        return $trip->trashed() ? null : $trip->id;
    }
}

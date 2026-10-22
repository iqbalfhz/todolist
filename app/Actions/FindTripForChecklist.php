<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FindTripForChecklist
{
    /**
     * Find the trip a new list is the checklist of.
     *
     * Answers 404 when the actor cannot reach the trip, 409 when it was
     * deleted, and 422 when it is in another space or already has a
     * checklist. Inside a transaction the trip stays locked, so two phones
     * cannot both give it a checklist.
     */
    public function handle(User $actor, string $tripId, string $spaceId): Trip
    {
        $trip = Trip::withTrashed()->whereKey($tripId)->lockForUpdate()->first();

        if ($trip === null) {
            throw (new ModelNotFoundException)->setModel(Trip::class, [$tripId]);
        }

        Gate::forUser($actor)->authorize('update', $trip);

        if ($trip->space_id !== $spaceId) {
            throw ValidationException::withMessages(['trip_id' => __('api.validation.trip_different_space')]);
        }

        if ($trip->trashed()) {
            throw new ApiException(ErrorCode::ParentDeleted, __('api.parent_deleted.trip'));
        }

        if (TaskList::withTrashed()->where('trip_id', $trip->id)->exists()) {
            throw ValidationException::withMessages(['trip_id' => __('api.validation.trip_has_checklist')]);
        }

        return $trip;
    }
}

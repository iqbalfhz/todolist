<?php

namespace App\Actions;

use App\Models\Space;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateTrip
{
    public function __construct(private CheckTripFields $checkTripFields) {}

    /**
     * Create a trip with the ID the phone chose.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): Trip
    {
        Gate::forUser($actor)->authorize('contribute', Space::query()->findOrFail($data['space_id']));

        return DB::transaction(function () use ($actor, $id, $data): Trip {
            $this->checkTripFields->ensureDatesFit($data['start_date'] ?? null, $data['end_date'] ?? null);

            $trip = new Trip(array_filter($data, fn (mixed $value): bool => $value !== null));
            $trip->id = $id;
            $trip->savings_goal_id = $this->checkTripFields->linkableSavingsGoalId($data['space_id'], $data['savings_goal_id'] ?? null);
            $trip->created_by = $actor->id;
            $trip->save();

            return $trip;
        });
    }
}

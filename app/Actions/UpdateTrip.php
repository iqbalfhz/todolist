<?php

namespace App\Actions;

use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class UpdateTrip
{
    public function __construct(private CheckTripFields $checkTripFields) {}

    /**
     * Apply the given changes to the trip.
     *
     * The dates are checked together with the stored ones, so changing only
     * the end date still has to fit the start date already saved.
     *
     * @param  array<string, mixed>  $changes
     */
    public function handle(Trip $trip, array $changes): Trip
    {
        return DB::transaction(function () use ($trip, $changes): Trip {
            $this->checkTripFields->ensureDatesFit(
                array_key_exists('start_date', $changes) ? $changes['start_date'] : $trip->start_date,
                array_key_exists('end_date', $changes) ? $changes['end_date'] : $trip->end_date,
            );

            if (array_key_exists('savings_goal_id', $changes)) {
                $changes['savings_goal_id'] = $this->checkTripFields->linkableSavingsGoalId($trip->space_id, $changes['savings_goal_id']);
            }

            $trip->update($changes);

            return $trip;
        });
    }
}

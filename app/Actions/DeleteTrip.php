<?php

namespace App\Actions;

use App\Models\TaskList;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class DeleteTrip
{
    /**
     * Soft delete the trip together with its checklist and the checklist's tasks.
     *
     * Everything is deleted through Eloquent, which also bumps updated_at, so
     * the deletions reach the other phone through sync.
     */
    public function handle(Trip $trip): void
    {
        DB::transaction(function () use ($trip): void {
            // Wait for a checklist that is being created for this trip, so it is deleted too.
            Trip::query()->whereKey($trip->id)->lockForUpdate()->first();

            $checklist = $trip->checklist()->first();

            if ($checklist !== null) {
                // Wait for tasks that are being written to the checklist, so none is left behind undeleted.
                TaskList::query()->whereKey($checklist->id)->lockForUpdate()->first();

                $checklist->tasks()->delete();
                $checklist->delete();
            }

            $trip->delete();
        });
    }
}

<?php

namespace App\Actions;

use App\Models\Memory;
use App\Models\TaskList;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class DeleteTrip
{
    /**
     * Soft delete the trip together with its checklist and the checklist's
     * tasks, and unlink the memories saved from it, which stay.
     *
     * Everything is changed through Eloquent, which also bumps updated_at, so
     * the changes reach the other phone through sync.
     */
    public function handle(Trip $trip): void
    {
        DB::transaction(function () use ($trip): void {
            // Wait for a checklist or a memory that is being linked to this trip, so it is handled too.
            Trip::query()->whereKey($trip->id)->lockForUpdate()->first();

            Memory::query()->where('trip_id', $trip->id)->update(['trip_id' => null]);

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

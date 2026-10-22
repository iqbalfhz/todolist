<?php

namespace App\Actions;

use App\Models\SavingsGoal;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class DeleteSavingsGoal
{
    /**
     * Soft delete the goal together with its entries, and unlink the trips saved up with it.
     *
     * The entries are deleted through Eloquent, which also bumps their
     * updated_at, so the deletions reach the other phone through sync.
     */
    public function handle(SavingsGoal $goal): void
    {
        DB::transaction(function () use ($goal): void {
            // Wait for entries that are being written to this goal, so none is left behind undeleted.
            SavingsGoal::query()->whereKey($goal->id)->lockForUpdate()->first();

            // An Eloquent update bumps updated_at, so the unlinked trips reach the other phone through sync.
            Trip::query()->where('savings_goal_id', $goal->id)->update(['savings_goal_id' => null]);

            $goal->entries()->delete();
            $goal->delete();
        });
    }
}

<?php

namespace App\Actions;

use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateSavingsEntry
{
    public function __construct(private RefreshSavingsProgress $refreshSavingsProgress) {}

    /**
     * Apply the given changes to the entry and recalculate its goal.
     *
     * @param  array<string, mixed>  $changes
     */
    public function handle(User $actor, SavingsEntry $entry, array $changes): SavingsEntry
    {
        return DB::transaction(function () use ($actor, $entry, $changes): SavingsEntry {
            $goal = SavingsGoal::query()->whereKey($entry->savings_goal_id)->lockForUpdate()->first();

            $entry->update($changes);

            if ($goal !== null) {
                $this->refreshSavingsProgress->handle($goal, $actor);
            }

            return $entry;
        });
    }
}

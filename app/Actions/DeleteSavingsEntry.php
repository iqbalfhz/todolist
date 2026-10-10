<?php

namespace App\Actions;

use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteSavingsEntry
{
    public function __construct(private RefreshSavingsProgress $refreshSavingsProgress) {}

    /**
     * Soft delete the entry and recalculate its goal.
     */
    public function handle(User $actor, SavingsEntry $entry): void
    {
        DB::transaction(function () use ($actor, $entry): void {
            $goal = SavingsGoal::query()->whereKey($entry->savings_goal_id)->lockForUpdate()->first();

            $entry->delete();

            if ($goal !== null) {
                $this->refreshSavingsProgress->handle($goal, $actor);
            }
        });
    }
}

<?php

namespace App\Actions;

use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteSavingsEntry
{
    public function __construct(
        private RefreshSavingsProgress $refreshSavingsProgress,
        private DeleteDiscussions $deleteDiscussions,
    ) {}

    /**
     * Soft delete the entry with its comments and reactions, and recalculate its goal.
     */
    public function handle(User $actor, SavingsEntry $entry): void
    {
        DB::transaction(function () use ($actor, $entry): void {
            $goal = SavingsGoal::query()->whereKey($entry->savings_goal_id)->lockForUpdate()->first();

            $entry->delete();
            $this->deleteDiscussions->handle(SavingsEntry::query()->whereKey($entry->id));

            if ($goal !== null) {
                $this->refreshSavingsProgress->handle($goal, $actor);
            }
        });
    }
}

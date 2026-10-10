<?php

namespace App\Actions;

use App\Models\SavingsGoal;
use App\Models\User;
use App\Rules\Iso8601WithOffset;
use Illuminate\Support\Facades\DB;

class UpdateSavingsGoal
{
    public function __construct(private RefreshSavingsProgress $refreshSavingsProgress) {}

    /**
     * Apply the given changes to the goal.
     *
     * @param  array<string, mixed>  $changes
     */
    public function handle(User $actor, SavingsGoal $goal, array $changes): SavingsGoal
    {
        return DB::transaction(function () use ($actor, $goal, $changes): SavingsGoal {
            $goal = SavingsGoal::query()->whereKey($goal->id)->lockForUpdate()->firstOrFail();

            if (isset($changes['archived_at'])) {
                $changes['archived_at'] = Iso8601WithOffset::toServerTime($changes['archived_at']);
            }

            $goal->fill($changes);

            $targetChanged = $goal->isDirty('target_amount');

            $goal->save();

            if ($targetChanged) {
                $this->refreshSavingsProgress->handle($goal, $actor);
            }

            return $goal;
        });
    }
}

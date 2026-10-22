<?php

namespace App\Actions;

use App\Models\SavingsGoal;
use App\Models\User;
use App\Rules\Iso8601WithOffset;
use Illuminate\Support\Facades\DB;

class UpdateSavingsGoal
{
    public function __construct(
        private RefreshSavingsProgress $refreshSavingsProgress,
        private NormalizeSavingsReminder $normalizeSavingsReminder,
    ) {}

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

            // A change to either reminder field is checked together with the one already stored.
            if (array_key_exists('reminder_frequency', $changes) || array_key_exists('reminder_day', $changes)) {
                $changes = [...$changes, ...$this->normalizeSavingsReminder->handle(
                    array_key_exists('reminder_frequency', $changes) ? $changes['reminder_frequency'] : $goal->reminder_frequency?->value,
                    array_key_exists('reminder_day', $changes) ? $changes['reminder_day'] : $goal->reminder_day,
                )];
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

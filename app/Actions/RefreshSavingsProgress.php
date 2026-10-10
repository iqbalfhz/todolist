<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\SavingsGoal;
use App\Models\User;

class RefreshSavingsProgress
{
    /**
     * Recalculate whether the goal is achieved after its entries or target changed.
     *
     * The caller must hold a lock on the goal row inside a transaction, so two
     * entries written at the same moment are both counted.
     *
     * @return bool Whether this call sent the one-time "achieved" push.
     */
    public function handle(SavingsGoal $goal, User $actor): bool
    {
        $isAchieved = (int) $goal->entries()->sum('amount') >= $goal->target_amount;

        if ($isAchieved) {
            $goal->achieved_at ??= now();
        } else {
            $goal->achieved_at = null;
        }

        $celebrates = $isAchieved && $goal->achieved_notified_at === null;

        if ($celebrates) {
            $goal->achieved_notified_at = now();
        }

        $goal->save();

        if ($celebrates) {
            $this->notifyOthers($goal, $actor, PushType::SavingsAchieved, __('push.savings_achieved.body'));
        }

        return $celebrates;
    }

    /**
     * Queue a push about the goal for every member of its space except the actor.
     */
    public function notifyOthers(SavingsGoal $goal, User $actor, PushType $type, string $body): void
    {
        $memberIds = $goal->space->members()->whereKeyNot($actor->id)->pluck('users.id');

        foreach ($memberIds as $memberId) {
            SendPushNotification::dispatch(
                $memberId,
                $type,
                $goal->name,
                $body,
                $goal->space_id,
                savingsGoalId: $goal->id,
            )->afterCommit();
        }
    }
}

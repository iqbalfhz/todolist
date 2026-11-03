<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\User;

class RefreshSavingsProgress
{
    public function __construct(private FormatMoney $formatMoney) {}

    /**
     * Recalculate whether the goal is achieved after its entries or target changed.
     *
     * The caller must hold a lock on the goal row inside a transaction, so two
     * entries written at the same moment are both counted. A caller that just
     * recorded a deposit passes it, so the "achieved" push can say who saved
     * how much when that deposit is the one that reaches the target.
     *
     * @return bool Whether this call sent the one-time "achieved" push.
     */
    public function handle(SavingsGoal $goal, User $actor, ?SavingsEntry $newDeposit = null): bool
    {
        $savedAmount = $goal->savedAmount();
        $isAchieved = $savedAmount >= $goal->target_amount;

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
            $body = $this->achievedBody($goal, $actor, $savedAmount - $goal->target_amount, $newDeposit);

            $this->notifyOthers($goal, $actor, PushType::SavingsAchieved, $body);
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

    /**
     * Word the "achieved" push: how far the total went past the target, and
     * who saved how much when a new deposit is what reached it.
     */
    private function achievedBody(SavingsGoal $goal, User $actor, int $excess, ?SavingsEntry $newDeposit): string
    {
        $replacements = ['excess' => $this->formatMoney->handle($excess, $goal->currency)];

        if ($newDeposit === null) {
            return __($excess > 0 ? 'push.savings_achieved.over_target_body' : 'push.savings_achieved.body', $replacements);
        }

        return __($excess > 0 ? 'push.savings_achieved.deposit_over_target_body' : 'push.savings_achieved.deposit_body', [
            ...$replacements,
            'name' => $actor->name,
            'amount' => $this->formatMoney->handle($newDeposit->amount, $goal->currency),
        ]);
    }
}

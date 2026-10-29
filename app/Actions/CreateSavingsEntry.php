<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Enums\SavingsEntryType;
use App\Models\SavingsEntry;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateSavingsEntry
{
    public function __construct(
        private FindWritableSavingsGoal $findWritableSavingsGoal,
        private RefreshSavingsProgress $refreshSavingsProgress,
        private FormatMoney $formatMoney,
    ) {}

    /**
     * Record a deposit or a withdrawal with the ID the phone chose.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): SavingsEntry
    {
        return DB::transaction(function () use ($actor, $id, $data): SavingsEntry {
            $goal = $this->findWritableSavingsGoal->handle($actor, $data['savings_goal_id']);

            $entry = new SavingsEntry(array_filter(
                Arr::only($data, ['type', 'amount', 'entry_date', 'note']),
                fn (mixed $value): bool => $value !== null,
            ));
            $entry->id = $id;
            $entry->savings_goal_id = $goal->id;
            $entry->space_id = $goal->space_id;
            $entry->user_id = $actor->id;
            $entry->save();

            $celebrates = $this->refreshSavingsProgress->handle($goal, $actor);
            $replacements = [
                'name' => $actor->name,
                'amount' => $this->formatMoney->handle($entry->amount, $goal->currency),
            ];

            if ($entry->type === SavingsEntryType::Withdrawal) {
                $this->refreshSavingsProgress->notifyOthers($goal, $actor, PushType::SavingsWithdrawal, __('push.savings_withdrawal.body', $replacements));
            } elseif (! $celebrates) {
                // The "achieved" push replaces the deposit push for the entry that reaches the target.
                $this->refreshSavingsProgress->notifyOthers($goal, $actor, PushType::SavingsDeposit, __('push.savings_deposit.body', $replacements));
            }

            return $entry;
        });
    }
}

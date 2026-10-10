<?php

namespace App\Actions;

use App\Enums\PushType;
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
     * Record a deposit with the ID the phone chose.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): SavingsEntry
    {
        return DB::transaction(function () use ($actor, $id, $data): SavingsEntry {
            $goal = $this->findWritableSavingsGoal->handle($data['savings_goal_id']);

            $entry = new SavingsEntry(array_filter(
                Arr::only($data, ['amount', 'entry_date', 'note']),
                fn (mixed $value): bool => $value !== null,
            ));
            $entry->id = $id;
            $entry->savings_goal_id = $goal->id;
            $entry->space_id = $goal->space_id;
            $entry->user_id = $actor->id;
            $entry->save();

            // The "achieved" push replaces the deposit push for the entry that reaches the target.
            if (! $this->refreshSavingsProgress->handle($goal, $actor)) {
                $this->refreshSavingsProgress->notifyOthers($goal, $actor, PushType::SavingsDeposit, __('push.savings_deposit.body', [
                    'name' => $actor->name,
                    'amount' => $this->formatMoney->handle($entry->amount, $goal->currency),
                ]));
            }

            return $entry;
        });
    }
}

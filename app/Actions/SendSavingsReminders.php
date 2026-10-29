<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Enums\SavingsReminderFrequency;
use App\Jobs\SendPushNotification;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SendSavingsReminders
{
    public function __construct(private DailyReminderWindow $window) {}

    /**
     * Push the deposit reminders of the goals whose weekday or day of the month is today.
     *
     * On the last day of a month, the monthly goals set to a later day are
     * reminded too, so day 31 falls on 30 April and on 28 or 29 February.
     * Goals that were achieved, archived, or deleted are not reminded.
     *
     * @return int The reminders sent.
     */
    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $sent = 0;

        $this->window->spacesDueNow($now)->each(function (Space $space) use ($now, &$sent): void {
            $today = $this->window->today($space, $now);

            $sent += SavingsGoal::query()
                ->where('space_id', $space->id)
                ->whereNull('archived_at')
                ->whereNull('achieved_at')
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $weekly) => $weekly
                        ->where('reminder_frequency', SavingsReminderFrequency::Weekly)
                        ->where('reminder_day', $today->dayOfWeekIso))
                    ->orWhere(fn (Builder $monthly) => $monthly
                        ->where('reminder_frequency', SavingsReminderFrequency::Monthly)
                        ->where('reminder_day', $today->isLastOfMonth() ? '>=' : '=', $today->day)))
                ->get()
                ->filter(fn (SavingsGoal $goal): bool => $this->window->send(
                    "goal:{$goal->id}",
                    $today,
                    fn () => $space->members->each(fn (User $member) => SendPushNotification::dispatch(
                        $member->id,
                        PushType::SavingsReminder,
                        $goal->name,
                        __('push.savings_reminder.body', ['percent' => $this->percentSaved($goal)]),
                        $space->id,
                        savingsGoalId: $goal->id,
                    )),
                ))
                ->count();
        });

        return $sent;
    }

    /**
     * Get the share of the target that was saved, rounded down to a whole percent.
     *
     * Withdrawals can take the saved amount below zero; the push then says 0%.
     */
    private function percentSaved(SavingsGoal $goal): int
    {
        return max(0, intdiv($goal->savedAmount() * 100, $goal->target_amount));
    }
}

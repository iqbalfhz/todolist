<?php

namespace App\Actions;

use App\Models\DailyReminder;
use App\Models\Space;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * The reminders that go out once a day at 08.00 in the timezone of a space:
 * special dates, member birthdays, and trips.
 */
class DailyReminderWindow
{
    /**
     * The local time of day the reminders go out.
     */
    public const REMIND_AT = '08:00';

    /**
     * Get the spaces where it is now between 08.00 and 08.30, with their members.
     *
     * A reminder that is more than 30 minutes late is skipped, like a task's.
     *
     * @return LazyCollection<int, Space>
     */
    public function spacesDueNow(CarbonImmutable $now): LazyCollection
    {
        return Space::query()
            ->whereIn('timezone', $this->timezonesDueNow($now))
            ->with('members')
            ->lazyById(200);
    }

    /**
     * Get the current day in the timezone of the space.
     */
    public function today(Space $space, CarbonImmutable $now): CarbonImmutable
    {
        return $now->setTimezone($space->timezone)->startOfDay();
    }

    /**
     * Claim the reminder for today and queue its pushes, unless another run already sent it.
     *
     * The claim and the queued pushes are saved together, so a run that is
     * cut short leaves the reminder for the next minute.
     *
     * @param  callable(): mixed  $queuePushes
     */
    public function send(string $reminderKey, CarbonImmutable $today, callable $queuePushes): bool
    {
        return DB::transaction(function () use ($reminderKey, $today, $queuePushes): bool {
            $reminder = new DailyReminder;
            $timestamp = now()->format($reminder->getDateFormat());

            $claimed = DailyReminder::query()->insertOrIgnore([
                'id' => $reminder->newUniqueId(),
                'reminder_key' => $reminderKey,
                'remind_on' => $today->toDateString(),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]) === 1;

            if ($claimed) {
                $queuePushes();
            }

            return $claimed;
        });
    }

    /**
     * Get the timezones of the spaces where the reminders are due now.
     *
     * @return list<string>
     */
    private function timezonesDueNow(CarbonImmutable $now): array
    {
        return Space::query()
            ->distinct()
            ->pluck('timezone')
            ->filter(function (string $timezone) use ($now): bool {
                $local = $now->setTimezone($timezone);
                $remindAt = $local->setTimeFromTimeString(self::REMIND_AT);

                return $local->between($remindAt, $remindAt->addMinutes(SendDueReminders::GRACE_MINUTES));
            })
            ->values()
            ->all();
    }
}

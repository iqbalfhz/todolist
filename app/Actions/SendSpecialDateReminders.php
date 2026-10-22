<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Enums\SpaceType;
use App\Jobs\SendPushNotification;
use App\Models\Space;
use App\Models\SpecialDate;
use App\Models\SpecialDateReminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendSpecialDateReminders
{
    /**
     * The local time of day the reminders go out.
     */
    public const REMIND_AT = '08:00';

    /**
     * How many days before the date each reminder goes out, with its text.
     *
     * @var array<int, string>
     */
    private const DAYS_BEFORE = [
        7 => 'push.special_date_reminder.in_a_week',
        1 => 'push.special_date_reminder.tomorrow',
        0 => 'push.special_date_reminder.today',
    ];

    /**
     * Push the reminders of the special dates and member birthdays that are 7 days, 1 day, or 0 days away.
     *
     * Only spaces where it is now between 08.00 and 08.30 are looked at, so
     * a reminder that is more than 30 minutes late is skipped, like a task's.
     *
     * @return int The reminders sent.
     */
    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $sent = 0;

        Space::query()
            ->whereIn('timezone', $this->timezonesAtReminderTime($now))
            ->with('members')
            ->lazyById(200)
            ->each(function (Space $space) use ($now, &$sent): void {
                $today = $now->setTimezone($space->timezone)->startOfDay();

                foreach (self::DAYS_BEFORE as $daysBefore => $text) {
                    $monthDays = $this->monthDays($today->addDays($daysBefore));

                    $sent += $this->remindOfSpecialDates($space, $today, $monthDays, $text);

                    if ($space->type === SpaceType::Shared) {
                        $sent += $this->remindOfBirthdays($space, $today, $monthDays, $text);
                    }
                }
            });

        return $sent;
    }

    /**
     * Get the timezones of the spaces where the reminders are due now.
     *
     * @return list<string>
     */
    private function timezonesAtReminderTime(CarbonImmutable $now): array
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

    /**
     * Get the month and day that fall on the given day, as stored after the year.
     *
     * 29 February is remembered on 28 February in a year that is not a leap year.
     *
     * @return list<string>
     */
    private function monthDays(CarbonImmutable $day): array
    {
        $monthDay = $day->format('m-d');

        return $monthDay === '02-28' && ! $day->isLeapYear() ? [$monthDay, '02-29'] : [$monthDay];
    }

    /**
     * Remind every member of the space of its dates that fall on the given month and day.
     *
     * @param  list<string>  $monthDays
     */
    private function remindOfSpecialDates(Space $space, CarbonImmutable $today, array $monthDays, string $text): int
    {
        $sent = 0;

        SpecialDate::query()
            ->where('space_id', $space->id)
            ->where(fn (Builder $query) => collect($monthDays)->each(
                fn (string $monthDay) => $query->orWhere('date', 'like', "%-{$monthDay}"),
            ))
            ->get()
            ->each(function (SpecialDate $specialDate) use ($space, $today, $text, &$sent): void {
                $sent += $this->send("date:{$specialDate->id}", $today, $space->members, fn (User $member) => SendPushNotification::dispatch(
                    $member->id,
                    PushType::SpecialDateReminder,
                    __('push.special_date_reminder.title'),
                    __($text, ['title' => $specialDate->title]),
                    $space->id,
                    specialDateId: $specialDate->id,
                ));
            });

        return $sent;
    }

    /**
     * Remind each member of the shared space of the partner's birthday.
     *
     * The member whose birthday it is gets no reminder, so it can be a surprise.
     *
     * @param  list<string>  $monthDays
     */
    private function remindOfBirthdays(Space $space, CarbonImmutable $today, array $monthDays, string $text): int
    {
        $sent = 0;

        $space->members
            ->filter(fn (User $member): bool => $member->birth_date !== null
                && Str::endsWith($member->birth_date, array_map(fn (string $monthDay): string => "-{$monthDay}", $monthDays)))
            ->each(function (User $birthdayMember) use ($space, $today, $text, &$sent): void {
                $partners = $space->members->reject(fn (User $member): bool => $member->is($birthdayMember));
                $title = __('push.special_date_reminder.birthday', ['name' => $birthdayMember->name]);

                $sent += $this->send("birthday:{$space->id}:{$birthdayMember->id}", $today, $partners, fn (User $partner) => SendPushNotification::dispatch(
                    $partner->id,
                    PushType::SpecialDateReminder,
                    __('push.special_date_reminder.title'),
                    __($text, ['title' => $title]),
                    $space->id,
                    memberId: $birthdayMember->id,
                ));
            });

        return $sent;
    }

    /**
     * Claim the reminder for today and queue its pushes, unless another run already sent it.
     *
     * The claim and the queued pushes are saved together, so a run that is
     * cut short leaves the reminder for the next minute.
     *
     * @param  Collection<int, User>  $recipients
     * @param  callable(User): mixed  $push
     */
    private function send(string $reminderKey, CarbonImmutable $today, Collection $recipients, callable $push): int
    {
        return DB::transaction(function () use ($reminderKey, $today, $recipients, $push): int {
            $reminder = new SpecialDateReminder;
            $timestamp = now()->format($reminder->getDateFormat());

            $claimed = SpecialDateReminder::query()->insertOrIgnore([
                'id' => $reminder->newUniqueId(),
                'reminder_key' => $reminderKey,
                'remind_on' => $today->toDateString(),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]) === 1;

            if (! $claimed) {
                return 0;
            }

            $recipients->each($push);

            return 1;
        });
    }
}

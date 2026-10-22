<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Enums\SpaceType;
use App\Jobs\SendPushNotification;
use App\Models\Space;
use App\Models\SpecialDate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SendSpecialDateReminders
{
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

    public function __construct(private DailyReminderWindow $window) {}

    /**
     * Push the reminders of the special dates and member birthdays that are 7 days, 1 day, or 0 days away.
     *
     * @return int The reminders sent.
     */
    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $sent = 0;

        $this->window->spacesDueNow($now)->each(function (Space $space) use ($now, &$sent): void {
            $today = $this->window->today($space, $now);

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
        return SpecialDate::query()
            ->where('space_id', $space->id)
            ->where(fn (Builder $query) => collect($monthDays)->each(
                fn (string $monthDay) => $query->orWhere('date', 'like', "%-{$monthDay}"),
            ))
            ->get()
            ->filter(fn (SpecialDate $specialDate): bool => $this->window->send(
                "date:{$specialDate->id}",
                $today,
                fn () => $space->members->each(fn (User $member) => SendPushNotification::dispatch(
                    $member->id,
                    PushType::SpecialDateReminder,
                    __('push.special_date_reminder.title'),
                    __($text, ['title' => $specialDate->title]),
                    $space->id,
                    specialDateId: $specialDate->id,
                )),
            ))
            ->count();
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
        return $space->members
            ->filter(fn (User $member): bool => $member->birth_date !== null
                && Str::endsWith($member->birth_date, array_map(fn (string $monthDay): string => "-{$monthDay}", $monthDays)))
            ->filter(function (User $birthdayMember) use ($space, $today, $text): bool {
                $title = __('push.special_date_reminder.birthday', ['name' => $birthdayMember->name]);

                return $this->window->send(
                    "birthday:{$space->id}:{$birthdayMember->id}",
                    $today,
                    fn () => $space->members
                        ->reject(fn (User $member): bool => $member->is($birthdayMember))
                        ->each(fn (User $partner) => SendPushNotification::dispatch(
                            $partner->id,
                            PushType::SpecialDateReminder,
                            __('push.special_date_reminder.title'),
                            __($text, ['title' => $title]),
                            $space->id,
                            memberId: $birthdayMember->id,
                        )),
                );
            })
            ->count();
    }
}

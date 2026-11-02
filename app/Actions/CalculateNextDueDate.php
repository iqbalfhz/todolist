<?php

namespace App\Actions;

use App\Enums\RepeatFrequency;
use Carbon\CarbonImmutable;

class CalculateNextDueDate
{
    /**
     * Get the first occurrence of a series that falls after the given day.
     *
     * Occurrences are counted from the anchor, never from the one before, so
     * a day a month lacks falls on its last day without moving the later
     * occurrences: 31 January, 28 February, 31 March. A weekly series with
     * weekdays falls on those days instead of the weekday of the anchor. All
     * days are YYYY-MM-DD in the timezone of the space.
     *
     * @param  list<int>|null  $days  ISO weekdays of a weekly series in ascending order, 1 being Monday
     */
    public function handle(RepeatFrequency $frequency, int $interval, string $anchor, string $after, ?array $days = null): string
    {
        $anchorDay = CarbonImmutable::createFromFormat('!Y-m-d', $anchor, 'UTC');
        $afterDay = CarbonImmutable::createFromFormat('!Y-m-d', $after, 'UTC');

        if (filled($days)) {
            return $this->nextWeekday($interval, $anchorDay, $afterDay, $days)->toDateString();
        }

        // The estimate is never later than the answer, so it only needs stepping forward.
        $count = max(1, intdiv($frequency->periodsBetween($anchorDay, $afterDay), $interval));

        while (($next = $frequency->addTo($anchorDay, $count * $interval))->lessThanOrEqualTo($afterDay)) {
            $count++;
        }

        return $next->toDateString();
    }

    /**
     * Get the first of the weekdays after the given day, in the weeks the series repeats in.
     *
     * Weeks run from Monday to Sunday and are counted from the week of the
     * anchor, so the series repeats in every interval-th week from it.
     *
     * @param  list<int>  $days
     */
    private function nextWeekday(int $interval, CarbonImmutable $anchorDay, CarbonImmutable $afterDay, array $days): CarbonImmutable
    {
        $firstWeek = $this->mondayOf($anchorDay);
        $week = $this->mondayOf($afterDay);

        if ($week->lessThan($firstWeek)) {
            $week = $firstWeek;
        }

        $weeksIntoInterval = intdiv((int) $firstWeek->diffInDays($week), 7) % $interval;

        if ($weeksIntoInterval !== 0) {
            $week = $week->addWeeks($interval - $weeksIntoInterval);
        }

        // Every day of the week after this one is later than the given day.
        while (true) {
            foreach ($days as $day) {
                if (($next = $week->addDays($day - 1))->greaterThan($afterDay)) {
                    return $next;
                }
            }

            $week = $week->addWeeks($interval);
        }
    }

    /**
     * Get the Monday of the week the day falls in.
     */
    private function mondayOf(CarbonImmutable $day): CarbonImmutable
    {
        return $day->subDays($day->dayOfWeekIso - 1);
    }
}

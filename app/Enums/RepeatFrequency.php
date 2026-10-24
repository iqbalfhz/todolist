<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

enum RepeatFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /**
     * Move a day forward by the given number of periods.
     *
     * A day the target month lacks falls on its last day, so 31 January plus
     * one month is 28 February, and 29 February plus one year is 28 February.
     */
    public function addTo(CarbonImmutable $day, int $periods): CarbonImmutable
    {
        return match ($this) {
            self::Daily => $day->addDays($periods),
            self::Weekly => $day->addWeeks($periods),
            self::Monthly => $day->addMonthsNoOverflow($periods),
            self::Yearly => $day->addYearsNoOverflow($periods),
        };
    }

    /**
     * Count the whole periods from one day to a later one.
     *
     * Months and years are counted by the calendar, without looking at the
     * days, so the count can be one more than the periods that fully passed.
     */
    public function periodsBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return match ($this) {
            self::Daily => (int) $from->diffInDays($to),
            self::Weekly => intdiv((int) $from->diffInDays($to), 7),
            self::Monthly => ($to->year - $from->year) * 12 + $to->month - $from->month,
            self::Yearly => $to->year - $from->year,
        };
    }
}

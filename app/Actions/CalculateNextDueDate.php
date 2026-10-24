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
     * occurrences: 31 January, 28 February, 31 March. All days are
     * YYYY-MM-DD in the timezone of the space.
     */
    public function handle(RepeatFrequency $frequency, int $interval, string $anchor, string $after): string
    {
        $anchorDay = CarbonImmutable::createFromFormat('!Y-m-d', $anchor, 'UTC');
        $afterDay = CarbonImmutable::createFromFormat('!Y-m-d', $after, 'UTC');

        // The estimate is never later than the answer, so it only needs stepping forward.
        $count = max(1, intdiv($frequency->periodsBetween($anchorDay, $afterDay), $interval));

        while (($next = $frequency->addTo($anchorDay, $count * $interval))->lessThanOrEqualTo($afterDay)) {
            $count++;
        }

        return $next->toDateString();
    }
}

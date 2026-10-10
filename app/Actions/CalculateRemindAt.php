<?php

namespace App\Actions;

use Carbon\CarbonImmutable;

class CalculateRemindAt
{
    /**
     * The time of day a task without a due time is reminded at.
     */
    public const DEFAULT_TIME = '08:00';

    /**
     * Work out the UTC moment a task should be reminded at.
     *
     * The due date and time are wall-clock values in the space's timezone.
     */
    public function handle(?string $dueDate, ?string $dueTime, string $timezone): ?CarbonImmutable
    {
        if ($dueDate === null) {
            return null;
        }

        $time = $dueTime === null ? self::DEFAULT_TIME : substr($dueTime, 0, 5);

        return CarbonImmutable::createFromFormat('Y-m-d H:i', "{$dueDate} {$time}", $timezone)->utc();
    }
}

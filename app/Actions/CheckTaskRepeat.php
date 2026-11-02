<?php

namespace App\Actions;

use App\Enums\RepeatFrequency;
use Illuminate\Validation\ValidationException;

class CheckTaskRepeat
{
    /**
     * Refuse a repeat rule on a task without a due date, or in the packing checklist of a trip.
     *
     * The due date is the anchor the occurrences are counted from, and a
     * checklist belongs to one trip, so neither can repeat.
     */
    public function ensureAllowed(?string $dueDate, ?string $tripId, string $field): void
    {
        if ($dueDate === null) {
            throw ValidationException::withMessages([$field => __('api.validation.repeat_needs_due_date')]);
        }

        if ($tripId !== null) {
            throw ValidationException::withMessages([$field => __('api.validation.repeat_in_trip_checklist')]);
        }
    }

    /**
     * Get the weekdays a task repeats on in ascending order, or null for the weekday of its anchor.
     *
     * Only a weekly task picks its weekdays.
     *
     * @param  list<int>|null  $days  ISO weekdays, 1 being Monday
     * @return list<int>|null
     */
    public function weekdays(?string $frequency, ?array $days): ?array
    {
        if ($days === null) {
            return null;
        }

        if ($frequency !== RepeatFrequency::Weekly->value) {
            throw ValidationException::withMessages(['repeat_days' => __('api.validation.repeat_days_weekly_only')]);
        }

        sort($days);

        return $days;
    }
}

<?php

namespace App\Actions;

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
}

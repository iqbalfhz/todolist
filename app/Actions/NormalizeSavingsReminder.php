<?php

namespace App\Actions;

use App\Enums\SavingsReminderFrequency;
use Illuminate\Validation\ValidationException;

class NormalizeSavingsReminder
{
    /**
     * Check the deposit reminder schedule of a goal and get it as it is stored.
     *
     * Without a frequency the goal has no reminder, so its day is cleared.
     * With one, the day is required and has to fit the frequency.
     *
     * @return array{reminder_frequency: string|null, reminder_day: int|null}
     */
    public function handle(?string $frequency, ?int $day): array
    {
        if ($frequency === null) {
            return ['reminder_frequency' => null, 'reminder_day' => null];
        }

        if ($day === null) {
            throw ValidationException::withMessages(['reminder_day' => __('api.validation.reminder_day_needed')]);
        }

        $lastDay = SavingsReminderFrequency::from($frequency)->lastDay();

        if ($day > $lastDay) {
            throw ValidationException::withMessages(['reminder_day' => __("api.validation.reminder_day_{$frequency}")]);
        }

        return ['reminder_frequency' => $frequency, 'reminder_day' => $day];
    }
}

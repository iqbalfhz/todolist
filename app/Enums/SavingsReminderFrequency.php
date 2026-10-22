<?php

namespace App\Enums;

enum SavingsReminderFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    /**
     * The highest reminder_day this frequency accepts.
     *
     * Monthly goes up to 31; a shorter month is reminded on its last day.
     */
    public function lastDay(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::Monthly => 31,
        };
    }
}

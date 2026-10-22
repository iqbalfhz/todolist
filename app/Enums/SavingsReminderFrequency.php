<?php

namespace App\Enums;

enum SavingsReminderFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    /**
     * The highest reminder_day this frequency accepts.
     *
     * Monthly stops at 28, so the day exists in every month.
     */
    public function lastDay(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::Monthly => 28,
        };
    }
}

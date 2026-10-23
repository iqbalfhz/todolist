<?php

namespace App\Enums;

enum PushType: string
{
    case TaskReminder = 'task_reminder';
    case TaskAssigned = 'task_assigned';
    case TaskCompleted = 'task_completed';
    case SavingsDeposit = 'savings_deposit';
    case SavingsAchieved = 'savings_achieved';
    case PartnerJoined = 'partner_joined';
    case SpecialDateReminder = 'special_date_reminder';
    case TripReminder = 'trip_reminder';
    case SavingsReminder = 'savings_reminder';
    case MemoryCreated = 'memory_created';

    /**
     * The notification preference that must be on for this push to be sent,
     * or null when the push is always sent.
     */
    public function preference(): ?string
    {
        return match ($this) {
            self::TaskReminder => 'task_reminders',
            self::TaskAssigned, self::TaskCompleted, self::MemoryCreated => 'partner_activity',
            self::SavingsDeposit, self::SavingsAchieved => 'savings_activity',
            self::PartnerJoined => null,
            self::SpecialDateReminder => 'special_dates',
            self::TripReminder => 'trip_reminders',
            self::SavingsReminder => 'savings_reminders',
        };
    }

    /**
     * The Android notification channel the app created for this push.
     */
    public function channel(): string
    {
        return in_array($this, [self::TaskReminder, self::SpecialDateReminder, self::TripReminder, self::SavingsReminder], true) ? 'pengingat' : 'aktivitas';
    }

    /**
     * The FCM delivery priority.
     */
    public function priority(): string
    {
        return match ($this) {
            self::TaskReminder, self::TaskAssigned, self::SpecialDateReminder, self::TripReminder, self::SavingsReminder => 'high',
            default => 'normal',
        };
    }

    /**
     * Whether the content must stay hidden on the lock screen.
     */
    public function isPrivate(): bool
    {
        return in_array($this, [self::SavingsDeposit, self::SavingsAchieved, self::SavingsReminder], true);
    }
}

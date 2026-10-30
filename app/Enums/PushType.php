<?php

namespace App\Enums;

enum PushType: string
{
    case TaskReminder = 'task_reminder';
    case TaskAssigned = 'task_assigned';
    case TaskCompleted = 'task_completed';
    case SavingsDeposit = 'savings_deposit';
    case SavingsAchieved = 'savings_achieved';
    case SavingsWithdrawal = 'savings_withdrawal';
    case PartnerJoined = 'partner_joined';
    case PartnerLeft = 'partner_left';
    case SpecialDateReminder = 'special_date_reminder';
    case TripReminder = 'trip_reminder';
    case SavingsReminder = 'savings_reminder';
    case MemoryCreated = 'memory_created';
    case CommentCreated = 'comment_created';
    case ReactionCreated = 'reaction_created';

    /**
     * The notification preference that must be on for this push to be sent,
     * or null when the push is always sent.
     */
    public function preference(): ?string
    {
        return match ($this) {
            self::TaskReminder => 'task_reminders',
            self::TaskAssigned, self::TaskCompleted, self::MemoryCreated, self::CommentCreated, self::ReactionCreated => 'partner_activity',
            self::SavingsDeposit, self::SavingsAchieved, self::SavingsWithdrawal => 'savings_activity',
            self::PartnerJoined, self::PartnerLeft => null,
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
        return in_array($this, [self::SavingsDeposit, self::SavingsAchieved, self::SavingsWithdrawal, self::SavingsReminder], true);
    }
}

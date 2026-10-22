<?php

namespace App\Actions;

use App\Models\SavingsGoal;
use Illuminate\Validation\ValidationException;

class CheckTripFields
{
    /**
     * Refuse an end date without a start date, or one before the start date.
     *
     * Both dates are YYYY-MM-DD, so comparing them as text compares the days.
     */
    public function ensureDatesFit(?string $startDate, ?string $endDate): void
    {
        if ($endDate === null) {
            return;
        }

        if ($startDate === null) {
            throw ValidationException::withMessages(['end_date' => __('api.validation.end_date_needs_start_date')]);
        }

        if ($endDate < $startDate) {
            throw ValidationException::withMessages(['end_date' => __('api.validation.end_date_before_start_date')]);
        }
    }

    /**
     * Get the savings goal ID to store for a trip in the given space.
     *
     * A goal that was deleted in the meantime, usually by the partner, is
     * stored as no link, so the rest of the change still goes through. Inside
     * a transaction the goal stays share-locked, so a deletion that unlinks
     * its trips waits until the trip is saved.
     */
    public function linkableSavingsGoalId(string $spaceId, ?string $savingsGoalId): ?string
    {
        if ($savingsGoalId === null) {
            return null;
        }

        $goal = SavingsGoal::withTrashed()->whereKey($savingsGoalId)->sharedLock()->first();

        if ($goal === null || $goal->space_id !== $spaceId) {
            throw ValidationException::withMessages(['savings_goal_id' => __('api.validation.savings_goal_different_space')]);
        }

        return $goal->trashed() ? null : $goal->id;
    }
}

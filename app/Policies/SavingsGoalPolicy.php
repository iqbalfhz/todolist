<?php

namespace App\Policies;

use App\Models\SavingsGoal;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class SavingsGoalPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can change, delete, or add entries to the goal.
     */
    public function update(User $user, SavingsGoal $savingsGoal): Response
    {
        return $this->allowMembersOf($savingsGoal->space_id, $user);
    }
}

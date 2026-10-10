<?php

namespace App\Policies;

use App\Models\SavingsEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class SavingsEntryPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can change or delete the entry. Either partner may, whoever recorded it.
     */
    public function update(User $user, SavingsEntry $savingsEntry): Response
    {
        return $this->allowMembersOf($savingsEntry->space_id, $user);
    }
}

<?php

namespace App\Policies;

use App\Models\SpecialDate;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class SpecialDatePolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can change or delete the date.
     */
    public function update(User $user, SpecialDate $specialDate): Response
    {
        return $this->allowMembersOf($specialDate->space_id, $user);
    }
}

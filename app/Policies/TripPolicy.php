<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class TripPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can change or delete the trip, or give it a checklist.
     */
    public function update(User $user, Trip $trip): Response
    {
        return $this->allowMembersOf($trip->space_id, $user);
    }
}

<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class SpacePolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can add lists, tasks, and savings to the space.
     */
    public function contribute(User $user, Space $space): Response
    {
        return $this->allowMembersOf($space->id, $user);
    }
}

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

    /**
     * Determine whether the user can close the space: every member can, also once it is closed.
     *
     * Asking again after the space was closed must answer like the first
     * time, so membership is checked on the space itself rather than through
     * the user's spaces, which leave closed ones out.
     */
    public function delete(User $user, Space $space): Response
    {
        return $space->members()->whereKey($user->id)->exists() ? Response::allow() : Response::denyAsNotFound();
    }
}

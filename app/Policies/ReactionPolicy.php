<?php

namespace App\Policies;

use App\Models\Reaction;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class ReactionPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can reach the reaction: every member of its space can.
     *
     * Deleting it is left to the one who reacted, see modify().
     */
    public function update(User $user, Reaction $reaction): Response
    {
        return $this->allowMembersOf($reaction->space_id, $user);
    }

    /**
     * Determine whether the user can delete the reaction: only the one who reacted can.
     */
    public function modify(User $user, Reaction $reaction): Response
    {
        return $reaction->user_id === $user->id ? Response::allow() : Response::deny();
    }
}

<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\Response;

trait ChecksSpaceMembership
{
    /**
     * Allow members of the space; everyone else is told the record does not exist.
     *
     * Answering 404 instead of 403 keeps another couple's data undiscoverable.
     */
    protected function allowMembersOf(string $spaceId, User $user): Response
    {
        return $user->isMemberOf($spaceId) ? Response::allow() : Response::denyAsNotFound();
    }
}

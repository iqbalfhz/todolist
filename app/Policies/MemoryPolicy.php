<?php

namespace App\Policies;

use App\Models\Memory;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class MemoryPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can change or delete the memory, or add photos to it.
     */
    public function update(User $user, Memory $memory): Response
    {
        return $this->allowMembersOf($memory->space_id, $user);
    }
}

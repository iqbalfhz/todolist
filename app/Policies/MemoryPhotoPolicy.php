<?php

namespace App\Policies;

use App\Models\MemoryPhoto;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class MemoryPhotoPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can reorder or delete the photo.
     */
    public function update(User $user, MemoryPhoto $photo): Response
    {
        return $this->allowMembersOf($photo->space_id, $user);
    }
}

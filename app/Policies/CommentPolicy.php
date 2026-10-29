<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Policies\Concerns\ChecksSpaceMembership;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    use ChecksSpaceMembership;

    /**
     * Determine whether the user can reach the comment: every member of its space can.
     *
     * Changing or deleting it is left to its author alone, see modify().
     */
    public function update(User $user, Comment $comment): Response
    {
        return $this->allowMembersOf($comment->space_id, $user);
    }

    /**
     * Determine whether the user can change or delete the comment: only its author can.
     */
    public function modify(User $user, Comment $comment): Response
    {
        return $comment->user_id === $user->id ? Response::allow() : Response::deny();
    }
}

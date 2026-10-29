<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use App\Models\Reaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Lets partners comment on and react to the model (see SubjectType).
 */
trait HasDiscussion
{
    /**
     * Get the comments on this subject.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'subject');
    }

    /**
     * Get the reactions to this subject.
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'subject');
    }
}

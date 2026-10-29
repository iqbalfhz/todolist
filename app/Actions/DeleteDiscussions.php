<?php

namespace App\Actions;

use App\Models\Comment;
use App\Models\Memory;
use App\Models\Reaction;
use App\Models\SavingsEntry;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

class DeleteDiscussions
{
    /**
     * Soft delete the comments and reactions on the subjects the query finds, deleted subjects included.
     *
     * Run it after the subjects themselves were deleted, in the same
     * transaction: a comment or reaction being added holds the subject's row,
     * so the deletion waits for it and then takes it along, and one that
     * comes later finds the subject deleted. The deletions go through
     * Eloquent, which also bumps updated_at, so they reach the other phone
     * through sync.
     *
     * @param  Builder<Task>|Builder<SavingsEntry>|Builder<Memory>  $subjects
     */
    public function handle(Builder $subjects): void
    {
        $subject = $subjects->getModel();
        $subjectIds = $subjects->withTrashed()->select($subject->getQualifiedKeyName());

        foreach ([Comment::class, Reaction::class] as $model) {
            $model::query()
                ->where('subject_type', $subject->getMorphClass())
                ->whereIn('subject_id', $subjectIds)
                ->delete();
        }
    }
}

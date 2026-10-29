<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Enums\SavingsEntryType;
use App\Enums\SpaceType;
use App\Enums\SubjectType;
use App\Exceptions\ApiException;
use App\Models\Memory;
use App\Models\SavingsEntry;
use App\Models\Space;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FindDiscussableSubject
{
    /**
     * Find and lock the task, savings entry, or memory a comment or a reaction is being added to.
     *
     * Answers 404 when the actor cannot reach the subject, 409 when it was
     * deleted, and 422 when it is in a personal space. The lock makes a
     * deletion of the subject wait until the comment or reaction is stored,
     * so the deletion takes it along.
     */
    public function handle(User $actor, SubjectType $type, string $id): Task|SavingsEntry|Memory
    {
        $model = $type->modelClass();
        $subject = $model::withTrashed()->whereKey($id)->lockForUpdate()->first();

        if ($subject === null) {
            throw (new ModelNotFoundException)->setModel($model, [$id]);
        }

        Gate::forUser($actor)->authorize('update', $subject);

        if ($subject->trashed()) {
            throw new ApiException(ErrorCode::ParentDeleted, __('api.parent_deleted.'.$this->deletedMessageKey($subject)));
        }

        if (! Space::query()->whereKey($subject->space_id)->where('type', SpaceType::Shared)->exists()) {
            throw ValidationException::withMessages([
                'subject_id' => __('api.validation.discussion_needs_shared_space'),
            ]);
        }

        return $subject;
    }

    /**
     * Get the key of the message that names the deleted subject, which tells a withdrawal from a deposit.
     */
    private function deletedMessageKey(Task|SavingsEntry|Memory $subject): string
    {
        if ($subject instanceof SavingsEntry && $subject->type === SavingsEntryType::Withdrawal) {
            return 'savings_withdrawal';
        }

        return $subject->getMorphClass();
    }
}

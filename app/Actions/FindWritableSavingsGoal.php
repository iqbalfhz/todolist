<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\SavingsGoal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class FindWritableSavingsGoal
{
    /**
     * Find and lock the goal an entry is being recorded against.
     *
     * Answers 404 when the user cannot reach the goal and 409 when it was
     * deleted. An archived goal still accepts entries.
     */
    public function handle(string $id): SavingsGoal
    {
        $goal = SavingsGoal::withTrashed()->whereKey($id)->lockForUpdate()->first();

        if ($goal === null) {
            throw (new ModelNotFoundException)->setModel(SavingsGoal::class, [$id]);
        }

        Gate::authorize('update', $goal);

        if ($goal->trashed()) {
            throw new ApiException(ErrorCode::ParentDeleted, __('api.parent_deleted.savings_goal'));
        }

        return $goal;
    }
}

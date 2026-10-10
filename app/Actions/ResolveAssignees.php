<?php

namespace App\Actions;

use App\Models\Space;
use Illuminate\Validation\ValidationException;

class ResolveAssignees
{
    /**
     * Check that every given user is a member of the space and return their IDs.
     *
     * @param  list<string>  $userIds
     * @return list<string>
     */
    public function handle(array $userIds, string $spaceId): array
    {
        $memberCount = Space::query()->findOrFail($spaceId)->members()->whereKey($userIds)->count();

        if ($memberCount !== count($userIds)) {
            throw ValidationException::withMessages([
                'assignee_ids' => __('api.validation.assignee_not_member'),
            ]);
        }

        return array_values($userIds);
    }
}

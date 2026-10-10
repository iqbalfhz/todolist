<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\Space;
use App\Models\TaskList;

class KeepOneActiveList
{
    /**
     * Refuse to archive or delete the last active list of a space.
     *
     * Call this inside the transaction that removes the list.
     */
    public function handle(TaskList $list): void
    {
        // Lock the space row, so two removals at the same moment cannot both pass the check below.
        Space::query()->whereKey($list->space_id)->lockForUpdate()->first();

        $isActive = TaskList::query()->whereKey($list->id)->active()->exists();

        if (! $isActive) {
            return;
        }

        $anotherIsActive = TaskList::query()
            ->where('space_id', $list->space_id)
            ->whereKeyNot($list->id)
            ->active()
            ->exists();

        if (! $anotherIsActive) {
            throw new ApiException(ErrorCode::LastList);
        }
    }
}

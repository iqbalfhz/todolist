<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class FindWritableMemory
{
    /**
     * Find the memory a photo is being added to, locking it when asked.
     *
     * Answers 404 when the actor cannot reach the memory and 409 when it was deleted.
     */
    public function handle(User $actor, string $id, bool $lock = false): Memory
    {
        $query = Memory::withTrashed()->whereKey($id);

        if ($lock) {
            $query->lockForUpdate();
        }

        $memory = $query->first();

        if ($memory === null) {
            throw (new ModelNotFoundException)->setModel(Memory::class, [$id]);
        }

        Gate::forUser($actor)->authorize('update', $memory);

        if ($memory->trashed()) {
            throw new ApiException(ErrorCode::ParentDeleted, __('api.parent_deleted.memory'));
        }

        return $memory;
    }
}

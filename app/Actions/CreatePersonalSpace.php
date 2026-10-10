<?php

namespace App\Actions;

use App\Enums\SpaceRole;
use App\Enums\SpaceType;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePersonalSpace
{
    /**
     * The name of the list every new space starts with.
     */
    public const DEFAULT_LIST_NAME = 'Tugas';

    /**
     * The position given to the first list or task in an empty container.
     */
    public const FIRST_POSITION = 1024;

    /**
     * Create the user's personal space together with its first task list.
     */
    public function handle(User $user): Space
    {
        return DB::transaction(function () use ($user): Space {
            $space = Space::query()->create([
                'name' => 'Ruang Pribadi',
                'type' => SpaceType::Personal,
                'timezone' => $user->timezone,
                'owner_id' => $user->id,
            ]);

            $space->members()->attach($user->id, [
                'role' => SpaceRole::Owner->value,
                'joined_at' => $space->created_at->format($space->getDateFormat()),
            ]);

            $space->taskLists()->create([
                'name' => self::DEFAULT_LIST_NAME,
                'position' => self::FIRST_POSITION,
                'created_by' => $user->id,
            ]);

            return $space;
        });
    }
}

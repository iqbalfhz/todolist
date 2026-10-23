<?php

namespace App\Actions;

use App\Jobs\NotifyMemoryCreated;
use App\Models\Memory;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateMemory
{
    public function __construct(private CheckMemoryFields $checkMemoryFields) {}

    /**
     * Create a memory with the ID the phone chose, and tell the other member about it later.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, string $id, array $data): Memory
    {
        $space = Space::query()->findOrFail($data['space_id']);

        Gate::forUser($actor)->authorize('contribute', $space);

        return DB::transaction(function () use ($actor, $id, $data, $space): Memory {
            $memory = new Memory(array_filter($data, fn (mixed $value): bool => $value !== null));
            $memory->id = $id;
            $memory->trip_id = $this->checkMemoryFields->linkableTripId($space->id, $data['trip_id'] ?? null);
            $memory->created_by = $actor->id;
            $memory->save();

            if ($space->members()->whereKeyNot($actor->id)->exists()) {
                NotifyMemoryCreated::dispatch($memory->id, $actor->id)
                    ->delay(now()->addMinutes(NotifyMemoryCreated::DELAY_MINUTES))
                    ->afterCommit();
            }

            return $memory;
        });
    }
}

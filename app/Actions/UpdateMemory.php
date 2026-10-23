<?php

namespace App\Actions;

use App\Models\Memory;
use Illuminate\Support\Facades\DB;

class UpdateMemory
{
    public function __construct(private CheckMemoryFields $checkMemoryFields) {}

    /**
     * Apply the given changes to the memory.
     *
     * @param  array<string, mixed>  $changes
     */
    public function handle(Memory $memory, array $changes): Memory
    {
        return DB::transaction(function () use ($memory, $changes): Memory {
            if (array_key_exists('trip_id', $changes)) {
                $changes['trip_id'] = $this->checkMemoryFields->linkableTripId($memory->space_id, $changes['trip_id']);
            }

            $memory->update($changes);

            return $memory;
        });
    }
}

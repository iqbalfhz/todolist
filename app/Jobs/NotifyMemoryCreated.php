<?php

namespace App\Jobs;

use App\Enums\PushType;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyMemoryCreated implements ShouldQueue
{
    use Queueable;

    /**
     * How long the push waits after the memory is created, so the photos the
     * creator is still uploading are there when the partner opens it.
     */
    public const DELAY_MINUTES = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $memoryId, public string $actorId) {}

    /**
     * Queue the push for every member of the space except the creator,
     * unless the memory was deleted or its shared space closed in the meantime.
     */
    public function handle(): void
    {
        $memory = Memory::query()->with('space')->find($this->memoryId);
        $actor = User::query()->find($this->actorId);

        if ($memory === null || $memory->space === null || $actor === null) {
            return;
        }

        $memberIds = $memory->space->members()->whereKeyNot($actor->id)->pluck('users.id');

        foreach ($memberIds as $memberId) {
            SendPushNotification::dispatch(
                $memberId,
                PushType::MemoryCreated,
                $memory->title,
                __('push.memory_created.body', ['name' => $actor->name]),
                $memory->space_id,
                memoryId: $memory->id,
            );
        }
    }
}

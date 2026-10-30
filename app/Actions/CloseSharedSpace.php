<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Enums\SpaceType;
use App\Jobs\SendPushNotification;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CloseSharedSpace
{
    /**
     * Close the shared space for both partners when one of them leaves, and tell the other.
     *
     * The space is soft deleted and keeps its members and its content, so the
     * same two partners get it back when they pair again within
     * Space::RESTORABLE_DAYS. Closing a space that is already closed changes
     * nothing, which makes a repeated request, or the partner leaving at the
     * same moment, answer the same way without a second push.
     */
    public function handle(User $actor, string $spaceId): void
    {
        DB::transaction(function () use ($actor, $spaceId): void {
            $space = Space::withTrashed()->whereKey($spaceId)->lockForUpdate()->first();

            if ($space === null) {
                throw (new ModelNotFoundException)->setModel(Space::class, [$spaceId]);
            }

            Gate::forUser($actor)->authorize('delete', $space);

            if ($space->type !== SpaceType::Shared) {
                throw ValidationException::withMessages([
                    'id' => __('api.validation.personal_space_cannot_be_closed'),
                ]);
            }

            if ($space->trashed()) {
                return;
            }

            $space->delete();

            foreach ($space->members()->whereKeyNot($actor->id)->pluck('users.id') as $partnerId) {
                SendPushNotification::dispatch(
                    $partnerId,
                    PushType::PartnerLeft,
                    $space->name,
                    __('push.partner_left.body', ['name' => $actor->name]),
                    $space->id,
                )->afterCommit();
            }
        });
    }
}

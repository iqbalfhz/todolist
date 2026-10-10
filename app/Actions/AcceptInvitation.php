<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Enums\ProfileColor;
use App\Enums\PushType;
use App\Enums\SpaceRole;
use App\Enums\SpaceType;
use App\Exceptions\ApiException;
use App\Jobs\SendPushNotification;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcceptInvitation
{
    public function __construct(private UpdateProfile $updateProfile) {}

    /**
     * Use an invitation code, creating the space the two partners share.
     *
     * @return array{0: Space, 1: bool} The shared space and whether this call created it.
     */
    public function handle(User $joiner, string $code): array
    {
        $code = $this->normalize($code);

        return DB::transaction(function () use ($joiner, $code): array {
            $inviterId = SpaceInvitation::query()->where('code', $code)->value('created_by');

            // Lock both accounts in ID order and then the invitation, so partners who
            // enter each other's code at the same moment cannot both create a space.
            User::query()->whereIn('id', array_filter([$joiner->id, $inviterId]))->orderBy('id')->lockForUpdate()->get();
            $invitation = SpaceInvitation::query()->where('code', $code)->lockForUpdate()->first();

            $existingSpace = $joiner->sharedSpace();

            if ($existingSpace !== null) {
                if ($invitation !== null && $existingSpace->members()->whereKey($invitation->created_by)->exists()) {
                    return [$existingSpace, false];
                }

                throw new ApiException(ErrorCode::SharedSpaceExists);
            }

            if ($invitation === null || ! $this->canBeAcceptedBy($invitation, $joiner)) {
                throw new ApiException(ErrorCode::InvitationInvalid);
            }

            return [$this->createSharedSpace($invitation, $joiner), true];
        });
    }

    /**
     * Bring typed codes into the stored form: uppercase, without spaces or hyphens.
     */
    private function normalize(string $code): string
    {
        return Str::upper(str_replace([' ', '-'], '', $code));
    }

    /**
     * Determine whether the invitation is unused, unexpired, and from someone else who is still unpaired.
     */
    private function canBeAcceptedBy(SpaceInvitation $invitation, User $joiner): bool
    {
        return $invitation->accepted_at === null
            && $invitation->revoked_at === null
            && $invitation->expires_at->isFuture()
            && $invitation->created_by !== $joiner->id
            && $invitation->creator->sharedSpace() === null;
    }

    /**
     * Create the shared space owned by the inviter and close every open invitation of both partners.
     */
    private function createSharedSpace(SpaceInvitation $invitation, User $joiner): Space
    {
        $inviter = $invitation->creator;

        $this->keepColorsDistinct($joiner, $inviter);

        $space = Space::query()->create([
            'name' => 'Ruang Bersama',
            'type' => SpaceType::Shared,
            'timezone' => $inviter->timezone,
            'owner_id' => $inviter->id,
        ]);

        $joinedAt = $space->created_at->format($space->getDateFormat());

        $space->members()->attach([
            $inviter->id => ['role' => SpaceRole::Owner->value, 'joined_at' => $joinedAt],
            $joiner->id => ['role' => SpaceRole::Member->value, 'joined_at' => $joinedAt],
        ]);

        $space->taskLists()->create([
            'name' => CreatePersonalSpace::DEFAULT_LIST_NAME,
            'position' => CreatePersonalSpace::FIRST_POSITION,
            'created_by' => $inviter->id,
        ]);

        $invitation->update([
            'accepted_at' => now(),
            'accepted_by' => $joiner->id,
            'space_id' => $space->id,
        ]);

        SpaceInvitation::query()
            ->whereIn('created_by', [$inviter->id, $joiner->id])
            ->active()
            ->update(['revoked_at' => now()]);

        SendPushNotification::dispatch(
            $inviter->id,
            PushType::PartnerJoined,
            $space->name,
            __('push.partner_joined.body', ['name' => $joiner->name]),
            $space->id,
        )->afterCommit();

        return $space;
    }

    /**
     * Give the joiner the first free color when both partners picked the same one.
     */
    private function keepColorsDistinct(User $joiner, User $inviter): void
    {
        if ($joiner->color !== $inviter->color) {
            return;
        }

        $joiner->color = collect(ProfileColor::cases())
            ->first(fn (ProfileColor $color) => $color->value !== $inviter->color)
            ->value;
        $joiner->save();

        $this->updateProfile->touchSpaces($joiner);
    }
}

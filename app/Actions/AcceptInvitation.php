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
use Illuminate\Database\Eloquent\Builder;
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
     * Give the partners their shared space and close every open invitation of both.
     *
     * Partners who closed a shared space together less than
     * Space::RESTORABLE_DAYS ago get that space back; everyone else gets a
     * new one, owned by the inviter.
     */
    private function createSharedSpace(SpaceInvitation $invitation, User $joiner): Space
    {
        $inviter = $invitation->creator;

        $this->keepColorsDistinct($joiner, $inviter);

        $closedSpace = $this->recentlyClosedSpaceOf($inviter, $joiner);

        $space = $closedSpace !== null
            ? $this->restore($closedSpace)
            : $this->createSpace($inviter, $joiner);

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
            __($closedSpace !== null ? 'push.partner_joined.restored_body' : 'push.partner_joined.body', ['name' => $joiner->name]),
            $space->id,
        )->afterCommit();

        return $space;
    }

    /**
     * Create a shared space owned by the inviter, with both partners and the first list.
     */
    private function createSpace(User $inviter, User $joiner): Space
    {
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

        return $space;
    }

    /**
     * Find the shared space the two partners closed together recently enough to get it back.
     *
     * The row is locked, so the daily purge cannot delete it while it is being restored.
     */
    private function recentlyClosedSpaceOf(User $inviter, User $joiner): ?Space
    {
        return Space::onlyTrashed()
            ->where('type', SpaceType::Shared)
            ->where('deleted_at', '>=', now()->subDays(Space::RESTORABLE_DAYS))
            ->whereHas('members', fn (Builder $members) => $members->whereKey($inviter->id))
            ->whereHas('members', fn (Builder $members) => $members->whereKey($joiner->id))
            ->latest('deleted_at')
            ->lockForUpdate()
            ->first();
    }

    /**
     * Open a closed shared space again, with everything it held when it was closed.
     *
     * The phones removed the content when the space closed, and a sync from
     * their cursor would skip rows that have not changed since. Bumping
     * updated_at on every row that is still there sends it all again.
     */
    private function restore(Space $space): Space
    {
        $space->restore();

        foreach (Space::CONTENT_MODELS as $model) {
            $model::query()
                ->where('space_id', $space->id)
                ->update(['updated_at' => (new $model)->freshTimestampString()]);
        }

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

<?php

namespace App\Actions;

use App\Enums\PushType;
use App\Jobs\DeleteStoredFiles;
use App\Jobs\SendPushNotification;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DeleteAccount
{
    public function __construct(private PurgeSpaceContent $purgeSpaceContent) {}

    /**
     * Delete the account and everything it holds for good.
     *
     * Every space of the user, open or closed, loses all it holds at once.
     * A shared space whose partner is still in it stays, closed and empty,
     * so the partner's phones remove it through sync; it goes with the
     * daily purge. Nothing can bring the account back.
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $spaces = Space::withTrashed()
                ->whereHas('members', fn (Builder $members) => $members->whereKey($user->id))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($spaces as $space) {
                $this->emptySpace($space, $user);
            }

            SpaceInvitation::query()
                ->where(fn (Builder $invitations) => $invitations
                    ->where('created_by', $user->id)
                    ->orWhere('accepted_by', $user->id))
                ->delete();

            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            if ($user->avatar_path !== null) {
                DeleteStoredFiles::dispatch(User::AVATAR_DISK, [$user->avatar_path])->afterCommit();
            }

            // Device tokens and memberships go with the row.
            $user->delete();
        });
    }

    /**
     * Delete what the space holds, then the space too unless a partner is still in it.
     *
     * A space the partner still has is handed to them, since its owner is
     * going, and closed if it was open, which tells the partner.
     */
    private function emptySpace(Space $space, User $user): void
    {
        $partnerIds = $space->members()->whereKeyNot($user->id)->pluck('users.id');

        $this->purgeSpaceContent->handle($space->id);

        if ($partnerIds->isEmpty()) {
            $space->forceDelete();

            return;
        }

        if ($space->owner_id === $user->id) {
            $space->owner_id = $partnerIds->first();
            $space->save();
        }

        if ($space->trashed()) {
            return;
        }

        $space->delete();

        foreach ($partnerIds as $partnerId) {
            SendPushNotification::dispatch(
                $partnerId,
                PushType::PartnerLeft,
                $space->name,
                __('push.partner_left.account_deleted_body', ['name' => $user->name]),
                $space->id,
            )->afterCommit();
        }
    }
}

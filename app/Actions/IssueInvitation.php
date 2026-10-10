<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\SpaceInvitation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class IssueInvitation
{
    /**
     * Get the user's active invitation code, or create one.
     *
     * @return array{0: SpaceInvitation, 1: bool} The invitation and whether this call created it.
     */
    public function handle(User $user, bool $renew = false): array
    {
        return DB::transaction(function () use ($user, $renew): array {
            // Serialise requests of the same account, so it never ends up with two active codes.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->sharedSpace() !== null) {
                throw new ApiException(ErrorCode::SharedSpaceExists);
            }

            $active = $user->invitations()->active()->latest()->first();

            if ($active !== null && ! $renew) {
                return [$active, false];
            }

            $user->invitations()->active()->update(['revoked_at' => now()]);

            return [$this->create($user), true];
        });
    }

    /**
     * Create an invitation with a code no other invitation has used.
     */
    private function create(User $user): SpaceInvitation
    {
        while (true) {
            try {
                return $user->invitations()->create([
                    'code' => $this->randomCode(),
                    'expires_at' => now()->addHours(SpaceInvitation::VALID_FOR_HOURS),
                ]);
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }
    }

    /**
     * Draw a code from the alphabet that leaves out look-alike characters.
     */
    private function randomCode(): string
    {
        $lastIndex = strlen(SpaceInvitation::ALPHABET) - 1;

        return collect(range(1, SpaceInvitation::CODE_LENGTH))
            ->map(fn () => SpaceInvitation::ALPHABET[random_int(0, $lastIndex)])
            ->implode('');
    }
}

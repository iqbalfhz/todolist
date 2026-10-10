<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAvatar
{
    public function __construct(private UpdateProfile $updateProfile) {}

    /**
     * Remove the user's profile photo, if they have one.
     */
    public function handle(User $user): void
    {
        $path = $user->avatar_path;

        if ($path === null) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill(['avatar_path' => null])->save();

            $this->updateProfile->touchSpaces($user);
        });

        Storage::disk(User::AVATAR_DISK)->delete($path);
    }
}

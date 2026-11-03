<?php

namespace App\Actions;

use App\Enums\AccountNotice;
use App\Mail\AccountNoticeMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ChangePassword
{
    /**
     * Set a new password, sign the account out of every other device, and tell the owner by email.
     *
     * The token this request came with stays, and so does the FCM token of
     * this phone when it is given; every other token goes, so the other
     * phones are signed out and stop receiving pushes.
     */
    public function handle(User $user, string $password, int|string|null $keptTokenId, ?string $keptFcmToken): void
    {
        DB::transaction(function () use ($user, $password, $keptTokenId, $keptFcmToken): void {
            $user->password = $password;
            $user->save();

            $user->tokens()
                ->when($keptTokenId !== null, fn ($query) => $query->whereKeyNot($keptTokenId))
                ->delete();

            $user->deviceTokens()
                ->when($keptFcmToken !== null, fn ($query) => $query->where('token', '!=', $keptFcmToken))
                ->delete();

            Mail::to($user)->queue(new AccountNoticeMail(AccountNotice::PasswordChanged, $user->name));
        });
    }
}

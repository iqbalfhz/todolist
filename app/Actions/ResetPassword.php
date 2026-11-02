<?php

namespace App\Actions;

use App\Enums\VerificationPurpose;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResetPassword
{
    public function __construct(private ConsumeVerificationCode $consumeVerificationCode) {}

    /**
     * Set a new password with an emailed code, sign out every device, and sign in this one.
     *
     * Every Sanctum token and FCM token goes, so a phone that is signed out
     * stops receiving pushes as well. The code proves the user reads that
     * inbox, so the email counts as verified too.
     *
     * @return string|null The token for this device, or null when there is no such account or the code does not work.
     */
    public function handle(?User $user, string $code, string $password, string $deviceName): ?string
    {
        if ($user === null) {
            return null;
        }

        // A mismatch returns rather than throws, so the try it used is saved.
        return DB::transaction(function () use ($user, $code, $password, $deviceName): ?string {
            if (! $this->consumeVerificationCode->handle($user, VerificationPurpose::PasswordReset, $code)) {
                return null;
            }

            $user->password = $password;
            $user->email_verified_at ??= now();
            $user->save();

            $user->tokens()->delete();
            $user->deviceTokens()->delete();

            return $user->createToken($deviceName)->plainTextToken;
        });
    }
}

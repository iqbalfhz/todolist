<?php

namespace App\Actions;

use App\Enums\VerificationPurpose;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConfirmEmail
{
    public function __construct(private ConsumeVerificationCode $consumeVerificationCode) {}

    /**
     * Mark the user's email verified with the code that was emailed to it.
     *
     * An email that is verified already stays so, whatever code is sent.
     *
     * @return bool Whether the email is verified now.
     */
    public function handle(User $user, string $code): bool
    {
        if ($user->email_verified_at !== null) {
            return true;
        }

        // A mismatch returns rather than throws, so the try it used is saved.
        return DB::transaction(function () use ($user, $code): bool {
            if (! $this->consumeVerificationCode->handle($user, VerificationPurpose::EmailVerification, $code)) {
                return false;
            }

            $user->email_verified_at = now();
            $user->save();

            return true;
        });
    }
}

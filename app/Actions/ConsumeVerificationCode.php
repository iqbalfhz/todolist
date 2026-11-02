<?php

namespace App\Actions;

use App\Enums\VerificationPurpose;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\DB;

class ConsumeVerificationCode
{
    /**
     * Use up the user's code for the purpose when the given one matches and still works.
     *
     * A wrong code counts as a try, and the last try allowed removes the
     * code, as its expiry does. The caller must not roll back after a
     * mismatch, or the try would not count.
     */
    public function handle(User $user, VerificationPurpose $purpose, string $code): bool
    {
        return DB::transaction(function () use ($user, $purpose, $code): bool {
            $verification = VerificationCode::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->lockForUpdate()
                ->first();

            if ($verification === null) {
                return false;
            }

            if ($verification->expires_at->isPast()) {
                $verification->delete();

                return false;
            }

            if (! hash_equals($verification->code_hash, VerificationCode::hash($code))) {
                $verification->attempts++;
                $verification->attempts >= VerificationCode::MAX_ATTEMPTS ? $verification->delete() : $verification->save();

                return false;
            }

            $verification->delete();

            return true;
        });
    }
}

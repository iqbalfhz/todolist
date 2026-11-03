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
     *
     * @return VerificationCode|null The code that was used up, which still holds its new email, or null when the given one does not work.
     */
    public function handle(User $user, VerificationPurpose $purpose, string $code): ?VerificationCode
    {
        return DB::transaction(function () use ($user, $purpose, $code): ?VerificationCode {
            $verification = VerificationCode::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->lockForUpdate()
                ->first();

            if ($verification === null) {
                return null;
            }

            if ($verification->expires_at->isPast()) {
                $verification->delete();

                return null;
            }

            if (! hash_equals($verification->code_hash, VerificationCode::hash($code))) {
                $verification->attempts++;
                $verification->attempts >= VerificationCode::MAX_ATTEMPTS ? $verification->delete() : $verification->save();

                return null;
            }

            $verification->delete();

            return $verification;
        });
    }
}

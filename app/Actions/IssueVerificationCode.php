<?php

namespace App\Actions;

use App\Enums\VerificationPurpose;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class IssueVerificationCode
{
    /**
     * Email the user a new code for the purpose, in place of the previous one.
     *
     * The user's row is locked first, so two requests at once leave one
     * code, and the email is queued once the code is saved. A code to change
     * the email goes to the new address, which is kept with the code.
     */
    public function handle(User $user, VerificationPurpose $purpose, ?string $newEmail = null): void
    {
        $code = collect(range(1, VerificationCode::LENGTH))->map(fn (): int => random_int(0, 9))->implode('');

        DB::transaction(function () use ($user, $purpose, $newEmail, $code): void {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            VerificationCode::query()->updateOrCreate(
                ['user_id' => $user->id, 'purpose' => $purpose],
                [
                    'new_email' => $newEmail,
                    'code_hash' => VerificationCode::hash($code),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(VerificationCode::VALID_FOR_MINUTES),
                ],
            );

            Mail::to($newEmail ?? $user)->queue(new VerificationCodeMail($purpose, $code, $user->name));
        });
    }
}

<?php

namespace App\Actions;

use App\Enums\AccountNotice;
use App\Enums\VerificationPurpose;
use App\Mail\AccountNoticeMail;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ConfirmEmailChange
{
    public function __construct(
        private ConsumeVerificationCode $consumeVerificationCode,
        private MaskEmail $maskEmail,
    ) {}

    /**
     * Switch the account to the new email with the code that was sent there, and tell the old address.
     *
     * A wrong code returns rather than throws, so the try it used is saved.
     * When another account took the new email in the meantime, the unique
     * index refuses it, the change is rolled back with the code, and the
     * error is on the email.
     *
     * @return bool Whether the email was changed.
     *
     * @throws ValidationException When the new email belongs to another account now, or this is the admin account.
     */
    public function handle(User $user, string $code): bool
    {
        if ($user->isAdmin()) {
            throw ValidationException::withMessages(['email' => __('api.account.admin_email')]);
        }

        try {
            return DB::transaction(function () use ($user, $code): bool {
                $verification = $this->consumeVerificationCode->handle($user, VerificationPurpose::EmailChange, $code);

                if ($verification === null) {
                    return false;
                }

                $newEmail = (string) $verification->new_email;
                $oldEmail = $user->email;

                $user->email = $newEmail;
                $user->email_verified_at = now();
                $user->save();

                Mail::to($oldEmail)->queue(new AccountNoticeMail(
                    AccountNotice::EmailChanged,
                    $user->name,
                    $this->maskEmail->handle($newEmail),
                ));

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            $user->refresh();

            throw ValidationException::withMessages(['email' => __('api.account.email_taken')]);
        }
    }
}

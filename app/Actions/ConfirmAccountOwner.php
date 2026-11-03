<?php

namespace App\Actions;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ConfirmAccountOwner
{
    public function __construct(private VerifyGoogleIdToken $verifyGoogleIdToken) {}

    /**
     * Make sure the request comes from the owner of the account before a change that cannot wait for the queue.
     *
     * The password counts when one is sent. Otherwise the ID token must come
     * from the Google account linked to this one, so an account without a
     * password can still confirm it is the owner.
     *
     * @throws ApiException With invalid_credentials or google_token_invalid.
     */
    public function handle(User $user, ?string $password, ?string $idToken, string $wrongPasswordMessage): void
    {
        if ($password !== null) {
            if (! Hash::check($password, $user->password)) {
                throw new ApiException(ErrorCode::InvalidCredentials, $wrongPasswordMessage);
            }

            return;
        }

        $googleAccount = $this->verifyGoogleIdToken->handle((string) $idToken);

        if ($user->google_id === null || $googleAccount['sub'] !== $user->google_id) {
            throw new ApiException(ErrorCode::GoogleTokenInvalid, __('api.account.other_google_account'));
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ChangePassword;
use App\Actions\ConfirmAccountOwner;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

class PasswordController extends Controller
{
    /**
     * Change the password, or set one for an account from Google, once the owner confirms it.
     *
     * Not sent from the offline queue. This phone stays signed in, and the
     * other phones are signed out.
     */
    public function update(UpdatePasswordRequest $request, ConfirmAccountOwner $confirmAccountOwner, ChangePassword $changePassword): Response
    {
        $user = $request->user();

        $confirmAccountOwner->handle(
            $user,
            $request->filled('current_password') ? $request->validated('current_password') : null,
            $request->validated('id_token'),
            __('api.account.wrong_current_password'),
        );

        $currentToken = $user->currentAccessToken();

        $changePassword->handle(
            $user,
            $request->validated('password'),
            $currentToken instanceof PersonalAccessToken ? $currentToken->getKey() : null,
            $request->filled('fcm_token') ? $request->validated('fcm_token') : null,
        );

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteAccount;
use App\Actions\UpdateProfile;
use App\Actions\VerifyGoogleIdToken;
use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateMeRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class MeController extends Controller
{
    /**
     * Show the signed-in user's own profile.
     */
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Change the fields of the profile that were sent.
     */
    public function update(UpdateMeRequest $request, UpdateProfile $updateProfile): UserResource
    {
        return new UserResource($updateProfile->handle($request->user(), $request->validated()));
    }

    /**
     * Delete the account and everything it holds, once its password or its Google account confirms it.
     *
     * Not sent from the offline queue: the phone wipes its own copy after 204.
     */
    public function destroy(DeleteAccountRequest $request, VerifyGoogleIdToken $verifyGoogleIdToken, DeleteAccount $deleteAccount): Response
    {
        $user = $request->user();

        if ($request->filled('password')) {
            if (! Hash::check($request->validated('password'), $user->password)) {
                throw new ApiException(ErrorCode::InvalidCredentials, __('api.account.wrong_password'));
            }
        } else {
            $googleAccount = $verifyGoogleIdToken->handle($request->validated('id_token'));

            if ($user->google_id === null || $googleAccount['sub'] !== $user->google_id) {
                throw new ApiException(ErrorCode::GoogleTokenInvalid, __('api.account.other_google_account'));
            }
        }

        $deleteAccount->handle($user);

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ConfirmAccountOwner;
use App\Actions\DeleteAccount;
use App\Actions\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateMeRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

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
     * The admin account stays, since someone else could otherwise register
     * its email and sign in to the admin panel.
     */
    public function destroy(DeleteAccountRequest $request, ConfirmAccountOwner $confirmAccountOwner, DeleteAccount $deleteAccount): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            throw ValidationException::withMessages(['email' => __('api.account.admin_delete')]);
        }

        $confirmAccountOwner->handle(
            $user,
            $request->filled('password') ? $request->validated('password') : null,
            $request->validated('id_token'),
            __('api.account.wrong_password'),
        );

        $deleteAccount->handle($user);

        return response()->noContent();
    }
}

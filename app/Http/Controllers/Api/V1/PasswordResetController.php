<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreatePersonalSpace;
use App\Actions\ResetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Set a new password with an emailed code, and sign in this device as login does.
     *
     * An email without an account gets the same answer as a wrong code.
     */
    public function store(ResetPasswordRequest $request, ResetPassword $resetPassword, CreatePersonalSpace $createPersonalSpace): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        $token = $resetPassword->handle(
            $user,
            $request->validated('code'),
            $request->validated('password'),
            $request->validated('device_name'),
        );

        if ($token === null) {
            throw ValidationException::withMessages(['code' => __('api.validation.verification_code')]);
        }

        // Accounts created outside registration, such as the admin, have no personal space yet.
        if ($user->personalSpace() === null) {
            $createPersonalSpace->handle($user);
        }

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ],
        ]);
    }
}

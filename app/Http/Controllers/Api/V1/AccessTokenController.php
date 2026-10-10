<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreatePersonalSpace;
use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AccessTokenController extends Controller
{
    /**
     * Sign in and issue a token for this device.
     */
    public function store(LoginRequest $request, CreatePersonalSpace $createPersonalSpace): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw new ApiException(ErrorCode::InvalidCredentials);
        }

        // Accounts created outside registration, such as the admin, have no personal space yet.
        if ($user->personalSpace() === null) {
            $createPersonalSpace->handle($user);
        }

        return response()->json([
            'data' => [
                'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Revoke the token in use and forget this device's FCM token.
     */
    public function destroy(LogoutRequest $request): Response
    {
        $user = $request->user();

        if ($request->filled('fcm_token')) {
            $user->deviceTokens()->where('token', $request->validated('fcm_token'))->delete();
        }

        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}

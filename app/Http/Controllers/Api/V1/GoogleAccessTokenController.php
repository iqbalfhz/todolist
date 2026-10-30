<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SignInWithGoogle;
use App\Actions\VerifyGoogleIdToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleSignInRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class GoogleAccessTokenController extends Controller
{
    /**
     * Sign in or register with a Google account, and issue a token for this device.
     *
     * Answers 201 when the account was just created and 200 otherwise.
     */
    public function store(GoogleSignInRequest $request, VerifyGoogleIdToken $verifyGoogleIdToken, SignInWithGoogle $signInWithGoogle): JsonResponse
    {
        $identity = $verifyGoogleIdToken->handle($request->validated('id_token'));

        [$user, $created] = $signInWithGoogle->handle($identity, $request->validated('timezone'));

        return response()->json([
            'data' => [
                'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
                'user' => new UserResource($user),
            ],
        ], $created ? 201 : 200);
    }
}

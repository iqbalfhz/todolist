<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreatePersonalSpace;
use App\Actions\IssueVerificationCode;
use App\Enums\VerificationPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    /**
     * Register an account, create its personal space, email a code to verify
     * the email, and issue a token for this device.
     */
    public function store(RegisterRequest $request, CreatePersonalSpace $createPersonalSpace, IssueVerificationCode $issueVerificationCode): JsonResponse
    {
        $user = DB::transaction(function () use ($request, $createPersonalSpace, $issueVerificationCode): User {
            $user = User::query()->create(array_filter(
                $request->safe()->only(['name', 'email', 'password', 'timezone']),
            ));

            $createPersonalSpace->handle($user);
            $issueVerificationCode->handle($user, VerificationPurpose::EmailVerification);

            return $user;
        });

        return response()->json([
            'data' => [
                'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
                'user' => new UserResource($user),
            ],
        ], 201);
    }
}

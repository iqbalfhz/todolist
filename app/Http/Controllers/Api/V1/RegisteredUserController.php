<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreatePersonalSpace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    /**
     * Register an account, create its personal space, and issue a token for this device.
     */
    public function store(RegisterRequest $request, CreatePersonalSpace $createPersonalSpace): JsonResponse
    {
        $user = DB::transaction(function () use ($request, $createPersonalSpace): User {
            $user = User::query()->create(array_filter(
                $request->safe()->only(['name', 'email', 'password', 'timezone']),
            ));

            $createPersonalSpace->handle($user);

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

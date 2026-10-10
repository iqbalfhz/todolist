<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    /**
     * Register this device's FCM token, moving it over when another account used it before.
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $device = DeviceToken::query()->updateOrCreate(
            ['token' => $request->validated('token')],
            ['user_id' => $request->user()->id, 'platform' => $request->validated('platform')],
        );

        return response()->json([
            'data' => [
                'token' => $device->token,
                'platform' => $device->platform,
            ],
        ], $device->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Forget one of the signed-in user's FCM tokens.
     */
    public function destroy(Request $request, string $token): Response
    {
        $request->user()->deviceTokens()->where('token', $token)->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteAvatar;
use App\Actions\StoreAvatar;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvatarRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AvatarController extends Controller
{
    /**
     * Replace the signed-in user's profile photo.
     */
    public function store(StoreAvatarRequest $request, StoreAvatar $storeAvatar): UserResource
    {
        return new UserResource($storeAvatar->handle($request->user(), $request->file('photo')));
    }

    /**
     * Remove the signed-in user's profile photo.
     */
    public function destroy(Request $request, DeleteAvatar $deleteAvatar): Response
    {
        $deleteAvatar->handle($request->user());

        return response()->noContent();
    }
}

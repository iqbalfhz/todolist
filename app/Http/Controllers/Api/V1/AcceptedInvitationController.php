<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Resources\SpaceResource;
use Illuminate\Http\JsonResponse;

class AcceptedInvitationController extends Controller
{
    /**
     * Use a partner's invitation code and return the space the two now share.
     */
    public function store(AcceptInvitationRequest $request, AcceptInvitation $acceptInvitation): JsonResponse
    {
        [$space, $created] = $acceptInvitation->handle($request->user(), $request->validated('code'));

        return (new SpaceResource($space->load('members')))
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }
}

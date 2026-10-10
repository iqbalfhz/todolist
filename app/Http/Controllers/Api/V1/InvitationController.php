<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\IssueInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Resources\InvitationResource;
use Illuminate\Http\JsonResponse;

class InvitationController extends Controller
{
    /**
     * Return the user's active invitation code, creating or renewing it when asked.
     */
    public function store(StoreInvitationRequest $request, IssueInvitation $issueInvitation): JsonResponse
    {
        [$invitation, $created] = $issueInvitation->handle($request->user(), $request->boolean('renew'));

        return (new InvitationResource($invitation))
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateReaction;
use App\Enums\SubjectType;
use App\Http\Requests\StoreReactionRequest;
use App\Http\Resources\ReactionResource;
use App\Models\Reaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReactionController extends QueuedResourceController
{
    /**
     * Store a reaction with the ID the phone chose, in place of the user's earlier reaction to the same subject.
     */
    public function store(Request $request, string $id, CreateReaction $createReaction): JsonResponse
    {
        $targetSpaceId = SubjectType::spaceIdOf($request->input('subject_type'), $request->input('subject_id'));

        return $this->createOnce($id, $targetSpaceId, fn (): Reaction => $createReaction->handle(
            $request->user(),
            $id,
            resolve(StoreReactionRequest::class)->validated(),
        ));
    }

    /**
     * Delete the reaction, which only the one who reacted may do.
     */
    public function destroy(string $id): Response
    {
        $reaction = $this->findOrFail($id);

        Gate::authorize('modify', $reaction);

        if (! $reaction->trashed()) {
            $reaction->delete();
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return Reaction::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new ReactionResource($record);
    }
}

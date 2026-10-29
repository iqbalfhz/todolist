<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateComment;
use App\Enums\SubjectType;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CommentController extends QueuedResourceController
{
    /**
     * Store a comment with the ID the phone chose.
     */
    public function store(Request $request, string $id, CreateComment $createComment): JsonResponse
    {
        $targetSpaceId = SubjectType::spaceIdOf($request->input('subject_type'), $request->input('subject_id'));

        return $this->createOnce($id, $targetSpaceId, fn (): Comment => $createComment->handle(
            $request->user(),
            $id,
            resolve(StoreCommentRequest::class)->validated(),
        ));
    }

    /**
     * Change the text of the comment, which only its author may do.
     */
    public function update(string $id): JsonResponse
    {
        $comment = $this->findOrFail($id);

        Gate::authorize('modify', $comment);

        $this->ensureNotDeleted($comment);

        $comment->update(resolve(UpdateCommentRequest::class)->validated());

        return $this->respond($comment, 200);
    }

    /**
     * Delete the comment, which only its author may do.
     */
    public function destroy(string $id): Response
    {
        $comment = $this->findOrFail($id);

        Gate::authorize('modify', $comment);

        if (! $comment->trashed()) {
            $comment->delete();
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return Comment::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new CommentResource($record);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Rules\LowercaseUlid;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Shared behaviour of the endpoints the app sends from its offline queue:
 * the ID comes from the phone, and every request must be safe to repeat.
 */
abstract class QueuedResourceController extends Controller
{
    /**
     * Get the soft-deletable model these endpoints manage.
     *
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    /**
     * Wrap a record in its API resource.
     */
    abstract protected function resource(Model $record): JsonResource;

    /**
     * Run the create callback, or answer 200 with the stored record when this PUT is a repeat.
     *
     * @param  Closure(): Model  $create
     */
    protected function createOnce(string $id, ?string $targetSpaceId, Closure $create): JsonResponse
    {
        $existing = $this->alreadyCreated($id, $targetSpaceId);

        if ($existing === null) {
            try {
                return $this->respond($create(), 201);
            } catch (UniqueConstraintViolationException) {
                // The same PUT arrived twice at the same moment and the other request won.
                $existing = $this->alreadyCreated($id, $targetSpaceId);
            }
        }

        return $this->respond($existing, 200);
    }

    /**
     * Find the record a PATCH or DELETE addresses, or answer 404 when it does not exist for this user.
     */
    protected function findOrFail(string $id): Model
    {
        $record = $this->find($id);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel($this->model(), [$id]);
        }

        Gate::authorize('update', $record);

        return $record;
    }

    /**
     * Answer 410 when the record was deleted, usually by the partner.
     */
    protected function ensureNotDeleted(Model $record): void
    {
        if ($record->trashed()) {
            throw new ApiException(ErrorCode::ResourceDeleted);
        }
    }

    /**
     * Build the response for a single record.
     */
    protected function respond(Model $record, int $status): JsonResponse
    {
        return $this->resource($record)->response()->setStatusCode($status);
    }

    /**
     * Get the record an earlier PUT with this ID created in the same space, or null when the ID is free.
     */
    private function alreadyCreated(string $id, ?string $targetSpaceId): ?Model
    {
        $record = $this->find($id);

        if ($record === null) {
            return null;
        }

        $inAnotherSpace = Gate::denies('update', $record)
            || ($targetSpaceId !== null && $targetSpaceId !== $record->space_id);

        if ($inAnotherSpace) {
            throw new ApiException(ErrorCode::IdConflict);
        }

        $this->ensureNotDeleted($record);

        return $record;
    }

    /**
     * Look up the record by the ID in the URL, including a soft-deleted one.
     *
     * Uppercase ULIDs are rejected rather than matched case-insensitively by the database.
     */
    private function find(string $id): ?Model
    {
        if (! LowercaseUlid::matches($id)) {
            throw ValidationException::withMessages([
                'id' => __('api.validation.lowercase_ulid', ['attribute' => __('api.attributes.id')]),
            ]);
        }

        return $this->model()::withTrashed()->find($id);
    }
}

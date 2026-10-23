<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateMemory;
use App\Actions\DeleteMemory;
use App\Actions\UpdateMemory;
use App\Http\Requests\StoreMemoryRequest;
use App\Http\Requests\UpdateMemoryRequest;
use App\Http\Resources\MemoryPhotoResource;
use App\Http\Resources\MemoryResource;
use App\Models\Memory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class MemoryController extends QueuedResourceController
{
    /**
     * Create a memory with the ID the phone chose.
     */
    public function store(Request $request, string $id, CreateMemory $createMemory): JsonResponse
    {
        $targetSpaceId = is_string($request->input('space_id')) ? $request->input('space_id') : null;

        return $this->createOnce($id, $targetSpaceId, fn (): Memory => $createMemory->handle(
            $request->user(),
            $id,
            resolve(StoreMemoryRequest::class)->validated(),
        ));
    }

    /**
     * Change the fields of the memory that were sent.
     */
    public function update(string $id, UpdateMemory $updateMemory): JsonResponse
    {
        $memory = $this->findOrFail($id);

        $this->ensureNotDeleted($memory);

        return $this->respond($updateMemory->handle($memory, resolve(UpdateMemoryRequest::class)->validated()), 200);
    }

    /**
     * Delete the memory together with its photos.
     */
    public function destroy(string $id, DeleteMemory $deleteMemory): Response
    {
        $memory = $this->findOrFail($id);

        if (! $memory->trashed()) {
            $deleteMemory->handle($memory);
        }

        return response()->noContent();
    }

    /**
     * List the photos of the memory in their order, with new signed URLs.
     *
     * The app calls this when the URLs it synced have expired. It is not sent
     * from the offline queue.
     */
    public function photos(string $id): AnonymousResourceCollection
    {
        $memory = $this->findOrFail($id);

        $this->ensureNotDeleted($memory);

        return MemoryPhotoResource::collection($memory->photos()->orderBy('position')->orderBy('id')->get());
    }

    protected function model(): string
    {
        return Memory::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new MemoryResource($record);
    }
}

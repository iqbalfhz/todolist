<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateMemoryPhoto;
use App\Actions\DeleteMemoryPhoto;
use App\Http\Requests\StoreMemoryPhotoRequest;
use App\Http\Requests\UpdateMemoryPhotoRequest;
use App\Http\Resources\MemoryPhotoResource;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Rules\LowercaseUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class MemoryPhotoController extends QueuedResourceController
{
    /**
     * Store an uploaded photo with the ID the phone chose.
     *
     * The method is POST because PHP does not read a multipart body sent
     * with PUT, but the answers are those of every other PUT.
     */
    public function store(Request $request, string $id, CreateMemoryPhoto $createMemoryPhoto): JsonResponse
    {
        return $this->createOnce($id, $this->targetSpaceId($request), fn (): MemoryPhoto => $createMemoryPhoto->handle(
            $request->user(),
            $id,
            resolve(StoreMemoryPhotoRequest::class)->validated(),
        ));
    }

    /**
     * Move the photo to another place in the order of its memory.
     */
    public function update(string $id): JsonResponse
    {
        $photo = $this->findOrFail($id);

        $this->ensureNotDeleted($photo);

        $photo->update(resolve(UpdateMemoryPhotoRequest::class)->validated());

        return $this->respond($photo, 200);
    }

    /**
     * Delete the photo.
     */
    public function destroy(string $id, DeleteMemoryPhoto $deleteMemoryPhoto): Response
    {
        $photo = $this->findOrFail($id);

        if (! $photo->trashed()) {
            $deleteMemoryPhoto->handle($photo);
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return MemoryPhoto::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new MemoryPhotoResource($record);
    }

    /**
     * Get the space of the memory the request names, when that memory exists.
     */
    private function targetSpaceId(Request $request): ?string
    {
        $memoryId = $request->input('memory_id');

        return LowercaseUlid::matches($memoryId)
            ? Memory::withTrashed()->whereKey($memoryId)->value('space_id')
            : null;
    }
}

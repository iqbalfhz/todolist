<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreSpecialDateRequest;
use App\Http\Requests\UpdateSpecialDateRequest;
use App\Http\Resources\SpecialDateResource;
use App\Models\Space;
use App\Models\SpecialDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SpecialDateController extends QueuedResourceController
{
    /**
     * Create a special date with the ID the phone chose.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $targetSpaceId = is_string($request->input('space_id')) ? $request->input('space_id') : null;

        return $this->createOnce($id, $targetSpaceId, function () use ($request, $id): SpecialDate {
            $data = resolve(StoreSpecialDateRequest::class)->validated();

            Gate::authorize('contribute', Space::query()->findOrFail($data['space_id']));

            $specialDate = new SpecialDate($data);
            $specialDate->id = $id;
            $specialDate->created_by = $request->user()->id;
            $specialDate->save();

            return $specialDate;
        });
    }

    /**
     * Change the fields of the date that were sent.
     */
    public function update(string $id): JsonResponse
    {
        $specialDate = $this->findOrFail($id);

        $this->ensureNotDeleted($specialDate);

        $specialDate->update(resolve(UpdateSpecialDateRequest::class)->validated());

        return $this->respond($specialDate, 200);
    }

    /**
     * Soft delete the date.
     */
    public function destroy(string $id): Response
    {
        $specialDate = $this->findOrFail($id);

        if (! $specialDate->trashed()) {
            $specialDate->delete();
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return SpecialDate::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new SpecialDateResource($record);
    }
}

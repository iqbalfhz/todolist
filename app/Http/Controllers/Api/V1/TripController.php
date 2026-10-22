<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTrip;
use App\Actions\DeleteTrip;
use App\Actions\UpdateTrip;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class TripController extends QueuedResourceController
{
    /**
     * Create a trip with the ID the phone chose.
     */
    public function store(Request $request, string $id, CreateTrip $createTrip): JsonResponse
    {
        $targetSpaceId = is_string($request->input('space_id')) ? $request->input('space_id') : null;

        return $this->createOnce($id, $targetSpaceId, fn (): Trip => $createTrip->handle(
            $request->user(),
            $id,
            resolve(StoreTripRequest::class)->validated(),
        ));
    }

    /**
     * Change the fields of the trip that were sent.
     */
    public function update(string $id, UpdateTrip $updateTrip): JsonResponse
    {
        $trip = $this->findOrFail($id);

        $this->ensureNotDeleted($trip);

        return $this->respond($updateTrip->handle($trip, resolve(UpdateTripRequest::class)->validated()), 200);
    }

    /**
     * Delete the trip together with its checklist.
     */
    public function destroy(string $id, DeleteTrip $deleteTrip): Response
    {
        $trip = $this->findOrFail($id);

        if (! $trip->trashed()) {
            $deleteTrip->handle($trip);
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return Trip::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new TripResource($record);
    }
}

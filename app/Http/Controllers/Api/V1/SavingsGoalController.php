<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteSavingsGoal;
use App\Actions\UpdateSavingsGoal;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Requests\UpdateSavingsGoalRequest;
use App\Http\Resources\SavingsGoalResource;
use App\Models\SavingsGoal;
use App\Models\Space;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SavingsGoalController extends QueuedResourceController
{
    /**
     * Create a savings goal with the ID the phone chose.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $targetSpaceId = is_string($request->input('space_id')) ? $request->input('space_id') : null;

        return $this->createOnce($id, $targetSpaceId, function () use ($request, $id): SavingsGoal {
            $data = resolve(StoreSavingsGoalRequest::class)->validated();

            Gate::authorize('contribute', Space::query()->findOrFail($data['space_id']));

            $goal = new SavingsGoal(array_filter($data, fn (mixed $value): bool => $value !== null));
            $goal->id = $id;
            $goal->created_by = $request->user()->id;
            $goal->save();

            return $goal;
        });
    }

    /**
     * Change the fields of the goal that were sent.
     */
    public function update(Request $request, string $id, UpdateSavingsGoal $updateSavingsGoal): JsonResponse
    {
        $goal = $this->findOrFail($id);

        $this->ensureNotDeleted($goal);

        return $this->respond(
            $updateSavingsGoal->handle($request->user(), $goal, resolve(UpdateSavingsGoalRequest::class)->validated()),
            200,
        );
    }

    /**
     * Delete the goal together with its entries.
     */
    public function destroy(string $id, DeleteSavingsGoal $deleteSavingsGoal): Response
    {
        $goal = $this->findOrFail($id);

        if (! $goal->trashed()) {
            $deleteSavingsGoal->handle($goal);
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return SavingsGoal::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new SavingsGoalResource($record);
    }
}

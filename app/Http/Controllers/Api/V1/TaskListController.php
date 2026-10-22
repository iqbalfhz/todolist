<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteTaskList;
use App\Actions\FindTripForChecklist;
use App\Actions\UpdateTaskList;
use App\Http\Requests\StoreTaskListRequest;
use App\Http\Requests\UpdateTaskListRequest;
use App\Http\Resources\TaskListResource;
use App\Models\Space;
use App\Models\TaskList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskListController extends QueuedResourceController
{
    /**
     * Create a task list with the ID the phone chose, or the checklist of a trip when trip_id is sent.
     */
    public function store(Request $request, string $id, FindTripForChecklist $findTripForChecklist): JsonResponse
    {
        $targetSpaceId = is_string($request->input('space_id')) ? $request->input('space_id') : null;

        return $this->createOnce($id, $targetSpaceId, function () use ($request, $id, $findTripForChecklist): TaskList {
            $data = resolve(StoreTaskListRequest::class)->validated();

            Gate::authorize('contribute', Space::query()->findOrFail($data['space_id']));

            return DB::transaction(function () use ($request, $id, $data, $findTripForChecklist): TaskList {
                if (isset($data['trip_id'])) {
                    $findTripForChecklist->handle($request->user(), $data['trip_id'], $data['space_id']);
                }

                $list = new TaskList(array_filter($data, fn (mixed $value): bool => $value !== null));
                $list->id = $id;
                $list->created_by = $request->user()->id;
                $list->save();

                return $list;
            });
        });
    }

    /**
     * Change the fields of the list that were sent, including its order and archive state.
     */
    public function update(string $id, UpdateTaskList $updateTaskList): JsonResponse
    {
        $list = $this->findOrFail($id);

        $this->ensureNotDeleted($list);

        return $this->respond(
            $updateTaskList->handle($list, resolve(UpdateTaskListRequest::class)->validated()),
            200,
        );
    }

    /**
     * Delete the list together with its tasks.
     */
    public function destroy(string $id, DeleteTaskList $deleteTaskList): Response
    {
        $list = $this->findOrFail($id);

        if (! $list->trashed()) {
            $deleteTaskList->handle($list);
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return TaskList::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new TaskListResource($record);
    }
}

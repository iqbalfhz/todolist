<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTask;
use App\Actions\UpdateTask;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskList;
use App\Rules\LowercaseUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class TaskController extends QueuedResourceController
{
    /**
     * Create a task with the ID the phone chose.
     */
    public function store(Request $request, string $id, CreateTask $createTask): JsonResponse
    {
        return $this->createOnce($id, $this->targetSpaceId($request), fn (): Task => $createTask->handle(
            $request->user(),
            $id,
            resolve(StoreTaskRequest::class)->validated(),
        ));
    }

    /**
     * Change the fields of the task that were sent, including completing it.
     */
    public function update(Request $request, string $id, UpdateTask $updateTask): JsonResponse
    {
        $task = $this->findOrFail($id);

        $this->ensureNotDeleted($task);

        return $this->respond(
            $updateTask->handle($request->user(), $task, resolve(UpdateTaskRequest::class)->validated()),
            200,
        );
    }

    /**
     * Delete the task.
     */
    public function destroy(string $id): Response
    {
        $task = $this->findOrFail($id);

        if (! $task->trashed()) {
            $task->delete();
        }

        return response()->noContent();
    }

    protected function model(): string
    {
        return Task::class;
    }

    protected function resource(Model $record): JsonResource
    {
        return new TaskResource($record);
    }

    /**
     * Get the space of the list the request names, when that list exists.
     */
    private function targetSpaceId(Request $request): ?string
    {
        $listId = $request->input('task_list_id');

        return LowercaseUlid::matches($listId)
            ? TaskList::withTrashed()->whereKey($listId)->value('space_id')
            : null;
    }
}

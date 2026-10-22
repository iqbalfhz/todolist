<?php

namespace App\Exceptions;

use App\Http\Resources\SavingsEntryResource;
use App\Http\Resources\SavingsGoalResource;
use App\Http\Resources\SpecialDateResource;
use App\Http\Resources\TaskListResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TripResource;
use App\Http\Resources\UserResource;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\SpecialDate;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;
use App\Rules\LowercaseUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentResourceResolver
{
    /**
     * The endpoints the app sends from its offline queue, keyed by route name
     * prefix, with the model and resource behind the ID in their URL.
     *
     * @var array<string, array{class-string<Model>, class-string<JsonResource>}>
     */
    private const QUEUED_RESOURCES = [
        'lists' => [TaskList::class, TaskListResource::class],
        'tasks' => [Task::class, TaskResource::class],
        'savings-goals' => [SavingsGoal::class, SavingsGoalResource::class],
        'savings-entries' => [SavingsEntry::class, SavingsEntryResource::class],
        'special-dates' => [SpecialDate::class, SpecialDateResource::class],
        'trips' => [Trip::class, TripResource::class],
    ];

    /**
     * Determine whether the request went to an endpoint the app sends from its offline queue.
     */
    public function appliesTo(Request $request): bool
    {
        return $request->routeIs('me.update') || $this->queuedResourceFor($request) !== null;
    }

    /**
     * Get the data behind the request URL as the server stores it now, or
     * null when it does not exist for this user.
     *
     * @return array<string, mixed>|null
     */
    public function resolve(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        if ($request->routeIs('me.update')) {
            return (new UserResource($user->fresh()))->resolve($request);
        }

        [$model, $resource] = $this->queuedResourceFor($request) ?? [null, null];

        if ($model === null) {
            return null;
        }

        $id = $request->route('id');

        $record = LowercaseUlid::matches($id) ? $model::withTrashed()->find($id) : null;

        if ($record === null || ! $user->isMemberOf($record->space_id)) {
            return null;
        }

        return (new $resource($record))->resolve($request);
    }

    /**
     * Get the model and resource for a queued endpoint.
     *
     * @return array{class-string<Model>, class-string<JsonResource>}|null
     */
    private function queuedResourceFor(Request $request): ?array
    {
        foreach (self::QUEUED_RESOURCES as $routePrefix => $classes) {
            if ($request->routeIs("{$routePrefix}.*")) {
                return $classes;
            }
        }

        return null;
    }
}

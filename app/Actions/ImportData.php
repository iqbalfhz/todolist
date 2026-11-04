<?php

namespace App\Actions;

use App\Http\Requests\StoreMemoryRequest;
use App\Http\Requests\StoreSavingsEntryRequest;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Requests\StoreSpecialDateRequest;
use App\Http\Requests\StoreTaskListRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\StoreTripRequest;
use App\Models\Memory;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\SpecialDate;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;
use App\Rules\Iso8601WithOffset;
use App\Rules\LowercaseUlid;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportData
{
    /**
     * The collections an import takes, in the order they are saved, so the
     * rows another row points to are saved before it.
     */
    public const COLLECTIONS = ['savings_goals', 'trips', 'task_lists', 'tasks', 'savings_entries', 'special_dates', 'memories'];

    /**
     * How many rows one request may hold, over all collections.
     */
    public const MAX_ROWS = 1000;

    /**
     * The open spaces of the importer, by ID.
     *
     * @var array<string, Space>
     */
    private array $spaces = [];

    /**
     * The IDs of the members of each of those spaces.
     *
     * @var array<string, list<string>>
     */
    private array $members = [];

    /**
     * The rows other rows may point to, by model and ID; null when no such row is in the importer's spaces.
     *
     * @var array<class-string<Model>, array<string, Model|null>>
     */
    private array $known = [];

    /**
     * The next task each imported task points to, linked once every task of the request is saved.
     *
     * @var array<string, string>
     */
    private array $nextTasks = [];

    /**
     * The goals that received entries, whose achievement is worked out at the end.
     *
     * @var array<string, true>
     */
    private array $goalsToRefresh = [];

    public function __construct(
        private CalculateRemindAt $calculateRemindAt,
        private CheckTaskRepeat $checkTaskRepeat,
        private CheckTripFields $checkTripFields,
        private NormalizeSavingsReminder $normalizeSavingsReminder,
    ) {}

    /**
     * Save the rows of an export file as new data in the importer's spaces, all of them or none.
     *
     * The phone gives every row a new ID, so a part sent again finds its
     * rows in place and skips them. The original authors and times stay
     * where the space allows it, and nothing sends a push but the sync push.
     *
     * @param  array<string, mixed>  $rows  The rows of each collection, as the phone sent them.
     * @return array{created: array<string, int>, skipped: array<string, int>}
     *
     * @throws ValidationException With the place of each row that cannot be saved, such as tasks.12.title.
     */
    public function handle(User $actor, array $rows): array
    {
        $rows = $this->validateRows($rows);

        return DB::transaction(function () use ($actor, $rows): array {
            // Two parts of one import sent at once run one after the other, so the second skips what the first saved.
            User::query()->whereKey($actor->id)->lockForUpdate()->first();

            $this->spaces = $actor->spaces()->get()->keyBy('id')->all();
            $this->members = array_map(fn (Space $space): array => $space->members()->pluck('users.id')->all(), $this->spaces);
            $this->known = [];
            $this->nextTasks = [];
            $this->goalsToRefresh = [];

            $counts = ['created' => array_fill_keys(self::COLLECTIONS, 0), 'skipped' => array_fill_keys(self::COLLECTIONS, 0)];

            foreach ($rows as $collection => $collectionRows) {
                foreach ($collectionRows as $index => $row) {
                    $created = $this->importRow($actor, $collection, $row, "{$collection}.{$index}");

                    $counts[$created ? 'created' : 'skipped'][$collection]++;
                }
            }

            $this->linkNextTasks();
            $this->refreshGoals();

            return $counts;
        });
    }

    /**
     * Check every row with the rules of its PUT, and keep only the fields those rules know.
     *
     * @param  array<string, mixed>  $rows
     * @return array<string, array<int, array<string, mixed>>>
     *
     * @throws ValidationException With the errors of every row at once.
     */
    private function validateRows(array $rows): array
    {
        $validated = [];
        $errors = [];

        foreach (self::COLLECTIONS as $collection) {
            $rules = $this->rulesFor($collection);
            $validated[$collection] = [];

            foreach ($rows[$collection] ?? [] as $index => $row) {
                $validator = Validator::make($row, $rules, [], __('api.attributes'));

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $field => $messages) {
                        $errors["{$collection}.{$index}.{$field}"] = $messages;
                    }

                    continue;
                }

                $validated[$collection][$index] = $validator->validated();
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    /**
     * Get the rules of a row: those of its PUT, with its ID and the fields an export keeps.
     *
     * @return array<string, mixed>
     */
    private function rulesFor(string $collection): array
    {
        $id = ['id' => ['required', new LowercaseUlid], 'created_at' => ['nullable', new Iso8601WithOffset]];
        $createdBy = ['created_by' => ['nullable', new LowercaseUlid]];
        $archivedAt = ['archived_at' => ['nullable', new Iso8601WithOffset]];

        return match ($collection) {
            'savings_goals' => [...$id, ...(new StoreSavingsGoalRequest)->rules(), ...$archivedAt, ...$createdBy],
            'trips' => [...$id, ...(new StoreTripRequest)->rules(), ...$createdBy],
            'task_lists' => [...$id, ...(new StoreTaskListRequest)->rules(), ...$archivedAt, ...$createdBy],
            'tasks' => [
                ...$id,
                ...(new StoreTaskRequest)->rules(),
                'completed_by' => ['nullable', new LowercaseUlid],
                'next_task_id' => ['nullable', new LowercaseUlid],
                ...$createdBy,
            ],
            'savings_entries' => [...$id, ...(new StoreSavingsEntryRequest)->rules(), 'user_id' => ['nullable', new LowercaseUlid]],
            'special_dates' => [...$id, ...(new StoreSpecialDateRequest)->rules(), ...$createdBy],
            'memories' => [...$id, ...(new StoreMemoryRequest)->rules(), ...$createdBy],
        };
    }

    /**
     * Save one row, unless it is in place already.
     *
     * @param  array<string, mixed>  $row
     * @return bool Whether the row was created rather than skipped.
     */
    private function importRow(User $actor, string $collection, array $row, string $path): bool
    {
        return match ($collection) {
            'savings_goals' => $this->importSavingsGoal($actor, $row, $path),
            'trips' => $this->importTrip($actor, $row, $path),
            'task_lists' => $this->importTaskList($actor, $row, $path),
            'tasks' => $this->importTask($actor, $row, $path),
            'savings_entries' => $this->importSavingsEntry($actor, $row, $path),
            'special_dates' => $this->importSpecialDate($actor, $row, $path),
            'memories' => $this->importMemory($actor, $row, $path),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importSavingsGoal(User $actor, array $row, string $path): bool
    {
        $space = $this->space($row['space_id'], $path);

        if ($this->isInPlace(SavingsGoal::class, $row['id'], $space->id, $path)) {
            return false;
        }

        $reminder = $this->within($path, fn (): array => $this->normalizeSavingsReminder->handle(
            $row['reminder_frequency'] ?? null,
            $row['reminder_day'] ?? null,
        ));

        $goal = new SavingsGoal([...$this->filled($row, ['name', 'emoji', 'target_amount', 'currency', 'target_date']), ...$reminder]);
        $goal->id = $row['id'];
        $goal->space_id = $space->id;
        $goal->archived_at = $this->time($row['archived_at'] ?? null);

        return $this->create($goal, $space, $row, $actor);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importTrip(User $actor, array $row, string $path): bool
    {
        $space = $this->space($row['space_id'], $path);

        if ($this->isInPlace(Trip::class, $row['id'], $space->id, $path)) {
            return false;
        }

        $this->within($path, fn () => $this->checkTripFields->ensureDatesFit($row['start_date'] ?? null, $row['end_date'] ?? null));

        $trip = new Trip($this->filled($row, ['title', 'destination', 'start_date', 'end_date', 'notes']));
        $trip->id = $row['id'];
        $trip->space_id = $space->id;
        $trip->savings_goal_id = $this->inSpace(SavingsGoal::class, $row['savings_goal_id'] ?? null, $space->id)?->getKey();

        return $this->create($trip, $space, $row, $actor);
    }

    /**
     * A checklist whose trip is missing, or whose trip has a checklist already, becomes a plain list.
     *
     * @param  array<string, mixed>  $row
     */
    private function importTaskList(User $actor, array $row, string $path): bool
    {
        $space = $this->space($row['space_id'], $path);

        if ($this->isInPlace(TaskList::class, $row['id'], $space->id, $path)) {
            return false;
        }

        $trip = $this->inSpace(Trip::class, $row['trip_id'] ?? null, $space->id);

        if ($trip !== null) {
            Trip::query()->whereKey($trip->getKey())->lockForUpdate()->first();

            if (TaskList::withTrashed()->where('trip_id', $trip->getKey())->exists()) {
                $trip = null;
            }
        }

        $list = new TaskList($this->filled($row, ['name', 'emoji', 'color', 'position']));
        $list->id = $row['id'];
        $list->space_id = $space->id;
        $list->trip_id = $trip?->getKey();
        $list->archived_at = $this->time($row['archived_at'] ?? null);

        return $this->create($list, $space, $row, $actor);
    }

    /**
     * A task goes to the space of its list, which may be archived but not deleted.
     *
     * @param  array<string, mixed>  $row
     */
    private function importTask(User $actor, array $row, string $path): bool
    {
        $list = $this->live(TaskList::class, $row['task_list_id']);

        if ($list === null) {
            throw ValidationException::withMessages(["{$path}.task_list_id" => __('api.import.task_list_missing')]);
        }

        $space = $this->spaces[$list->space_id];

        if ($this->isInPlace(Task::class, $row['id'], $space->id, $path)) {
            return false;
        }

        $task = new Task($this->filled($row, ['title', 'notes', 'due_date', 'due_time', 'priority', 'position']));
        $task->id = $row['id'];
        $task->task_list_id = $list->getKey();
        $task->space_id = $space->id;
        $task->updated_by = $actor->id;
        $task->remind_at = $this->calculateRemindAt->handle($task->due_date, $task->due_time, $space->timezone);
        $task->reminder_sent_at = $task->remind_at?->lte(now()) ? now() : null;

        if (isset($row['repeat_frequency'])) {
            $this->within($path, fn () => $this->checkTaskRepeat->ensureAllowed($task->due_date, $list->trip_id, 'repeat_frequency'));

            $task->repeat_frequency = $row['repeat_frequency'];
            $task->repeat_interval = $row['repeat_interval'] ?? 1;
            $task->repeat_anchor = $task->due_date;
        }

        $task->repeat_days = $this->within($path, fn (): ?array => $this->checkTaskRepeat->weekdays(
            $row['repeat_frequency'] ?? null,
            $row['repeat_days'] ?? null,
        ));

        if (isset($row['completed_at'])) {
            $task->completed_at = $this->time($row['completed_at']);
            $task->completed_by = $this->member($space, $row['completed_by'] ?? null, $actor);
        }

        $this->create($task, $space, $row, $actor);

        $task->assignees()->sync($this->assignees($space, $row['assignee_ids'] ?? null, $actor));

        if (isset($row['next_task_id'])) {
            $this->nextTasks[$task->id] = $row['next_task_id'];
        }

        return true;
    }

    /**
     * An entry goes to the space of its goal, whose achievement is worked out once every entry is saved.
     *
     * @param  array<string, mixed>  $row
     */
    private function importSavingsEntry(User $actor, array $row, string $path): bool
    {
        $goal = $this->live(SavingsGoal::class, $row['savings_goal_id']);

        if ($goal === null) {
            throw ValidationException::withMessages(["{$path}.savings_goal_id" => __('api.import.savings_goal_missing')]);
        }

        $space = $this->spaces[$goal->space_id];

        if ($this->isInPlace(SavingsEntry::class, $row['id'], $space->id, $path)) {
            return false;
        }

        $entry = new SavingsEntry($this->filled($row, ['type', 'amount', 'entry_date', 'note']));
        $entry->id = $row['id'];
        $entry->savings_goal_id = $goal->getKey();
        $entry->space_id = $space->id;
        $entry->user_id = $this->member($space, $row['user_id'] ?? null, $actor);
        $entry->created_at = $this->createdAt($row);
        $entry->save();

        $this->goalsToRefresh[$goal->getKey()] = true;

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importSpecialDate(User $actor, array $row, string $path): bool
    {
        $space = $this->space($row['space_id'], $path);

        if ($this->isInPlace(SpecialDate::class, $row['id'], $space->id, $path)) {
            return false;
        }

        $specialDate = new SpecialDate(Arr::only($row, ['type', 'title', 'date']));
        $specialDate->id = $row['id'];
        $specialDate->space_id = $space->id;

        return $this->create($specialDate, $space, $row, $actor);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importMemory(User $actor, array $row, string $path): bool
    {
        $space = $this->space($row['space_id'], $path);

        if ($this->isInPlace(Memory::class, $row['id'], $space->id, $path)) {
            return false;
        }

        $memory = new Memory($this->filled($row, ['title', 'place_name', 'latitude', 'longitude', 'visited_on', 'story']));
        $memory->id = $row['id'];
        $memory->space_id = $space->id;
        $memory->trip_id = $this->inSpace(Trip::class, $row['trip_id'] ?? null, $space->id)?->getKey();

        return $this->create($memory, $space, $row, $actor);
    }

    /**
     * Save a new row with its original author and creation time, and remember it for the rows that point to it.
     *
     * @param  array<string, mixed>  $row
     */
    private function create(Model $model, Space $space, array $row, User $actor): bool
    {
        $model->setAttribute('created_by', $this->member($space, $row['created_by'] ?? null, $actor));
        $model->setAttribute($model->getCreatedAtColumn(), $this->createdAt($row));
        $model->save();

        $this->known[$model::class][$model->getKey()] = $model;

        return true;
    }

    /**
     * Point the imported tasks to their next tasks, now that all of them are saved.
     *
     * A next task outside the space, or already the next task of another
     * one, is left out, so the link stays one to one.
     */
    private function linkNextTasks(): void
    {
        foreach ($this->nextTasks as $taskId => $nextTaskId) {
            $task = $this->known[Task::class][$taskId];
            $nextTask = $this->inSpace(Task::class, $nextTaskId, $task->space_id);

            if ($nextTask === null || $nextTask->getKey() === $taskId || Task::withTrashed()->where('next_task_id', $nextTaskId)->exists()) {
                continue;
            }

            $task->next_task_id = $nextTaskId;
            $task->save();
        }
    }

    /**
     * Mark the goals that the imported entries took to their target as achieved, without the push.
     */
    private function refreshGoals(): void
    {
        foreach (array_keys($this->goalsToRefresh) as $goalId) {
            $goal = SavingsGoal::query()->whereKey($goalId)->lockForUpdate()->first();

            if ($goal->savedAmount() >= $goal->target_amount) {
                $goal->achieved_at ??= now();
                $goal->achieved_notified_at ??= now();
            } else {
                $goal->achieved_at = null;
            }

            $goal->save();
        }
    }

    /**
     * Get the importer's open space with the given ID.
     *
     * @throws ValidationException When the space is not one of the importer's.
     */
    private function space(string $spaceId, string $path): Space
    {
        return $this->spaces[$spaceId]
            ?? throw ValidationException::withMessages(["{$path}.space_id" => __('api.validation.space_not_member')]);
    }

    /**
     * Tell whether a row with this ID is in the space already, so it is skipped.
     *
     * A row of an earlier part, deleted or not, counts. The same ID in
     * another space is refused.
     *
     * @param  class-string<Model>  $model
     *
     * @throws ValidationException When the ID belongs to a row in another space.
     */
    private function isInPlace(string $model, string $id, string $spaceId, string $path): bool
    {
        $existing = $model::withTrashed()->whereKey($id)->first();

        if ($existing === null) {
            return false;
        }

        if ($existing->space_id !== $spaceId) {
            throw ValidationException::withMessages(["{$path}.id" => __('api.import.id_taken')]);
        }

        $this->known[$model][$id] = $existing->trashed() ? null : $existing;

        return true;
    }

    /**
     * Find a live row in one of the importer's spaces, from this request or from before.
     *
     * A row from before is locked, as PUT locks the list of a task or the
     * goal of an entry, so it cannot be deleted while rows are added to it.
     *
     * @param  class-string<Model>  $model
     */
    private function live(string $model, ?string $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        if (! array_key_exists($id, $this->known[$model] ?? [])) {
            $this->known[$model][$id] = $model::query()
                ->whereKey($id)
                ->whereIn('space_id', array_keys($this->spaces))
                ->lockForUpdate()
                ->first();
        }

        return $this->known[$model][$id];
    }

    /**
     * Find a live row in the given space; a row anywhere else counts as missing.
     *
     * @param  class-string<Model>  $model
     */
    private function inSpace(string $model, ?string $id, string $spaceId): ?Model
    {
        $row = $this->live($model, $id);

        return $row !== null && $row->space_id === $spaceId ? $row : null;
    }

    /**
     * Keep the original person when they are a member of the space, or else use the importer.
     */
    private function member(Space $space, ?string $userId, User $actor): string
    {
        return $userId !== null && in_array($userId, $this->members[$space->id], true) ? $userId : $actor->id;
    }

    /**
     * Keep the assignees who are members of the space, or else assign the task to the importer.
     *
     * @param  list<string>|null  $assigneeIds
     * @return list<string>
     */
    private function assignees(Space $space, ?array $assigneeIds, User $actor): array
    {
        $members = array_values(array_intersect($assigneeIds ?? [], $this->members[$space->id]));

        return $members === [] ? [$actor->id] : $members;
    }

    /**
     * Get the original creation time from the file, never later than now.
     *
     * @param  array<string, mixed>  $row
     */
    private function createdAt(array $row): CarbonImmutable
    {
        return $this->time($row['created_at'] ?? null) ?? CarbonImmutable::now();
    }

    /**
     * Turn a time from the file into server time, never later than now.
     */
    private function time(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : Iso8601WithOffset::toServerTime($value);
    }

    /**
     * Get the given fields of the row that are not null, so the columns keep their defaults.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function filled(array $row, array $fields): array
    {
        return array_filter(Arr::only($row, $fields), fn (mixed $value): bool => $value !== null);
    }

    /**
     * Run a check shared with PUT, and place its errors at the row.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $check
     * @return TResult
     */
    private function within(string $path, Closure $check): mixed
    {
        try {
            return $check();
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $field): array => ["{$path}.{$field}" => $messages])->all(),
            );
        }
    }
}

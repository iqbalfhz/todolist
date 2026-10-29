<?php

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

describe('store', function () {
    it('creates the task with the ID from the phone and returns 201', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2026-10-08 10:31:00.123456');
        [$user, $partner, $space] = actingAsPartnered();
        $list = TaskList::factory()->for($space)->create();
        $id = newId();

        $response = $this->putJson("/api/v1/tasks/{$id}", [
            'task_list_id' => $list->id,
            'title' => 'Beli galon',
            'due_date' => '2026-10-09',
            'due_time' => '17:00',
            'position' => 2048,
            'assignee_ids' => [$partner->id],
        ]);

        $response->assertCreated()->assertExactJson([
            'data' => [
                'id' => $id,
                'task_list_id' => $list->id,
                'space_id' => $space->id,
                'title' => 'Beli galon',
                'notes' => null,
                'due_date' => '2026-10-09',
                'due_time' => '17:00',
                'repeat_frequency' => null,
                'repeat_interval' => null,
                'priority' => 0,
                'position' => 2048,
                'assignee_ids' => [$partner->id],
                'remind_at' => '2026-10-09T10:00:00.000000Z',
                'completed_at' => null,
                'completed_by' => null,
                'next_task_id' => null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => '2026-10-08T10:31:00.123456Z',
                'updated_at' => '2026-10-08T10:31:00.123456Z',
                'deleted_at' => null,
            ],
            'next_task' => null,
        ]);
        $this->assertDatabaseHas('task_assignees', ['task_id' => $id, 'user_id' => $partner->id]);
    });

    it('makes the creator responsible when no assignee is sent', function () {
        $user = actingAsApiUser();
        $list = $user->personalSpace()->taskLists()->sole();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $list->id,
            'title' => 'Beli galon',
            'position' => 2048,
        ]);

        $response->assertCreated()->assertJsonPath('data.assignee_ids', [$user->id]);
    });

    it('accepts both partners as assignees', function () {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $list = TaskList::factory()->for($space)->create();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $list->id,
            'title' => 'Bersih-bersih',
            'position' => 2048,
            'assignee_ids' => [$user->id, $partner->id],
        ]);

        $response->assertCreated();
        expect($response->json('data.assignee_ids'))->toEqualCanonicalizing([$user->id, $partner->id]);
    });

    it('calculates the reminder from the due date in the timezone of the space', function (array $due, ?string $remindAt) {
        $user = actingAsApiUser();
        $list = $user->personalSpace()->taskLists()->sole();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $list->id,
            'title' => 'Beli galon',
            'position' => 2048,
            ...$due,
        ]);

        $response->assertCreated()->assertJsonPath('data.remind_at', $remindAt);
    })->with([
        'at the due time' => [['due_date' => '2026-10-09', 'due_time' => '17:00'], '2026-10-09T10:00:00.000000Z'],
        'at 08.00 when there is no due time' => [['due_date' => '2026-10-09'], '2026-10-09T01:00:00.000000Z'],
        'never when there is no due date' => [[], null],
    ]);

    it('records who completed a task that arrives already completed', function () {
        $this->travelTo('2026-10-08 12:00:00');
        $user = actingAsApiUser();
        $list = $user->personalSpace()->taskLists()->sole();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $list->id,
            'title' => 'Beli galon',
            'position' => 2048,
            'completed_at' => '2026-10-08T18:30:00+07:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.completed_at', '2026-10-08T11:30:00.000000Z')
            ->assertJsonPath('data.completed_by', $user->id);
    });

    it('returns 200 with the stored task and changes nothing when the PUT is repeated', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create(['title' => 'Beli galon']);

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'task_list_id' => $task->task_list_id,
            'title' => 'Judul dari kiriman ulang',
            'position' => 9999,
        ]);

        $response->assertOk()->assertJsonPath('data.title', 'Beli galon');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Beli galon']);
    });

    it('returns 410 with the resource_deleted code when the ID belongs to a deleted task', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
        $task->delete();

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'task_list_id' => $task->task_list_id,
            'title' => 'Beli galon',
            'position' => 2048,
        ]);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 409 with the id_conflict code when the ID is used in another space', function (Closure $existingSpace) {
        $user = actingAsApiUser();
        $task = Task::factory()->for(TaskList::factory()->for($existingSpace($user)))->create();

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'task_list_id' => $user->personalSpace()->taskLists()->sole()->id,
            'title' => 'Beli galon',
            'position' => 2048,
        ]);

        $response->assertConflict()->assertJsonPath('code', 'id_conflict');
        $this->assertDatabaseCount('tasks', 1);
    })->with([
        'the space shared with the partner' => [fn (User $user) => sharedSpaceBetween($user, User::factory()->create())],
        'a space of another couple' => [fn () => Space::factory()->create()],
    ]);

    it('returns 404 when the list is not one the user can reach', function (Closure $listId) {
        actingAsApiUser();
        $id = newId();

        $response = $this->putJson("/api/v1/tasks/{$id}", [
            'task_list_id' => $listId(),
            'title' => 'Beli galon',
            'position' => 2048,
        ]);

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertDatabaseMissing('tasks', ['id' => $id]);
    })->with([
        'a list in the space of another couple' => [fn () => TaskList::factory()->create()->id],
        'a list that was never created' => [fn () => newId()],
    ]);

    it('returns 409 when the list was deleted or archived', function (Closure $closeList, string $code, string $message) {
        $user = actingAsApiUser();
        $list = TaskList::factory()->for($user->personalSpace())->create();
        $closeList($list);
        $id = newId();

        $response = $this->putJson("/api/v1/tasks/{$id}", [
            'task_list_id' => $list->id,
            'title' => 'Beli galon',
            'position' => 2048,
        ]);

        $response->assertConflict()->assertExactJson([
            'message' => $message,
            'code' => $code,
            'current' => null,
        ]);
        $this->assertDatabaseMissing('tasks', ['id' => $id]);
    })->with([
        'deleted' => [fn (TaskList $list) => $list->delete(), 'parent_deleted', 'Daftar ini sudah dihapus.'],
        'archived' => [fn (TaskList $list) => $list->update(['archived_at' => now()]), 'parent_archived', 'Daftar ini sudah diarsipkan.'],
    ]);

    it('returns 422 for invalid values', function (array $overrides, string $field) {
        $user = actingAsApiUser();
        $id = newId();

        $response = $this->putJson("/api/v1/tasks/{$id}", [
            'task_list_id' => $user->personalSpace()->taskLists()->sole()->id,
            'title' => 'Beli galon',
            'position' => 2048,
            ...$overrides,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('current', null)
            ->assertJsonValidationErrors([$field]);
        $this->assertDatabaseMissing('tasks', ['id' => $id]);
    })->with([
        'no list' => [['task_list_id' => null], 'task_list_id'],
        'no title' => [['title' => ''], 'title'],
        'a title longer than 200 characters' => [['title' => str_repeat('a', 201)], 'title'],
        'notes longer than 5,000 characters' => [['notes' => str_repeat('a', 5001)], 'notes'],
        'a due date in another format' => [['due_date' => '09-10-2026'], 'due_date'],
        'a due date that is not on the calendar' => [['due_date' => '2026-02-30'], 'due_date'],
        'a due time with seconds' => [['due_date' => '2026-10-09', 'due_time' => '17:00:00'], 'due_time'],
        'a due time without a due date' => [['due_time' => '17:00'], 'due_date'],
        'a priority above 3' => [['priority' => 4], 'priority'],
        'no position' => [['position' => null], 'position'],
        'a position too far from zero to store' => [['position' => '1e400'], 'position'],
        'an empty list of assignees' => [['assignee_ids' => []], 'assignee_ids'],
        'null as assignees' => [['assignee_ids' => null], 'assignee_ids'],
        'a completion time without an offset' => [['completed_at' => '2026-10-08T10:15:30'], 'completed_at'],
    ]);

    it('returns 422 for assignees the space does not allow', function (Closure $assigneeIds, string $field) {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $list = TaskList::factory()->for($space)->create();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $list->id,
            'title' => 'Beli galon',
            'position' => 2048,
            'assignee_ids' => $assigneeIds($user, $partner),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
        $this->assertDatabaseEmpty('tasks');
    })->with([
        'someone outside the space' => [fn () => [User::factory()->create()->id], 'assignee_ids'],
        'three people' => [fn (User $user, User $partner) => [$user->id, $partner->id, User::factory()->create()->id], 'assignee_ids'],
        'the same person twice' => [fn (User $user) => [$user->id, $user->id], 'assignee_ids.0'],
    ]);

    it('returns 422 when the partner is assigned a task in the personal space', function () {
        [$user, $partner] = actingAsPartnered();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $user->personalSpace()->taskLists()->sole()->id,
            'title' => 'Beli galon',
            'position' => 2048,
            'assignee_ids' => [$partner->id],
        ]);

        $response->assertUnprocessable()->assertJsonPath('errors.assignee_ids.0', 'Penanggung jawab harus anggota ruang ini.');
    });

    it('returns 422 on the id field when the ULID in the URL is in uppercase', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/tasks/01M4DH1YN0T6CDN39Q4F091895', [
            'task_list_id' => $user->personalSpace()->taskLists()->sole()->id,
            'title' => 'Beli galon',
            'position' => 2048,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['id']);
        $this->assertDatabaseEmpty('tasks');
    });

    it('queues a task_assigned push for each assignee other than the creator', function () {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $list = TaskList::factory()->for($space)->create();
        $id = newId();

        $this->putJson("/api/v1/tasks/{$id}", [
            'task_list_id' => $list->id,
            'title' => 'Beli galon',
            'position' => 2048,
            'assignee_ids' => [$user->id, $partner->id],
        ]);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::TaskAssigned
            && $job->title === 'Beli galon'
            && $job->body === 'Iqbal menitipkan tugas ini ke kamu'
            && $job->spaceId === $space->id
            && $job->taskId === $id);
    });

    it('queues no push for a task the creator keeps', function () {
        Queue::fake([SendPushNotification::class]);
        [$user, , $space] = actingAsPartnered();
        $list = TaskList::factory()->for($space)->create();

        $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $list->id,
            'title' => 'Beli galon',
            'position' => 2048,
        ]);

        Queue::assertNotPushed(SendPushNotification::class);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->putJson('/api/v1/tasks/'.newId(), []);

        $response->assertUnauthorized();
    });
});

describe('update', function () {
    it('changes only the fields that were sent and records who changed them', function () {
        [$user, $partner, $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create([
            'title' => 'Beli galon',
            'priority' => 1,
            'created_by' => $partner->id,
            'updated_by' => $partner->id,
        ]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Beli galon dua', 'notes' => 'Yang 19 liter']);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Beli galon dua')
            ->assertJsonPath('data.notes', 'Yang 19 liter')
            ->assertJsonPath('data.priority', 1)
            ->assertJsonPath('data.created_by', $partner->id)
            ->assertJsonPath('data.updated_by', $user->id);
    });

    it('moves the task to another active list in the same space', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
        $target = TaskList::factory()->for($user->personalSpace())->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['task_list_id' => $target->id]);

        $response->assertOk()->assertJsonPath('data.task_list_id', $target->id);
    });

    it('returns 422 when the target list is in another space of the user', function () {
        [$user, , $space] = actingAsPartnered();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
        $target = TaskList::factory()->for($space)->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['task_list_id' => $target->id]);

        $response->assertUnprocessable()->assertJsonPath('errors.task_list_id.0', 'Daftar tujuan harus berada di ruang yang sama.');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'task_list_id' => $task->task_list_id]);
    });

    it('refuses to move the task to a list that is closed or out of reach', function (Closure $target, int $status, string $code) {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['task_list_id' => $target($user)->id]);

        $response->assertStatus($status)
            ->assertJsonPath('code', $code)
            ->assertJsonPath('current.task_list_id', $task->task_list_id);
    })->with([
        'a deleted list' => [fn (User $user) => tap(TaskList::factory()->for($user->personalSpace())->create())->delete(), 409, 'parent_deleted'],
        'an archived list' => [fn (User $user) => TaskList::factory()->for($user->personalSpace())->archived()->create(), 409, 'parent_archived'],
        'a list of another couple' => [fn () => TaskList::factory()->create(), 404, 'not_found'],
    ]);

    it('recalculates the reminder and lets it fire again when the due date changes', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-09',
            'due_time' => '17:00',
            'remind_at' => '2026-10-09 10:00:00',
            'reminder_sent_at' => '2026-10-09 10:00:05',
        ]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['due_date' => '2026-10-10', 'due_time' => '09:30']);

        $response->assertOk()
            ->assertJsonPath('data.due_date', '2026-10-10')
            ->assertJsonPath('data.due_time', '09:30')
            ->assertJsonPath('data.remind_at', '2026-10-10T02:30:00.000000Z');
        expect($task->fresh()->reminder_sent_at)->toBeNull();
    });

    it('keeps the reminder as sent when the same due date is sent again', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-09',
            'due_time' => '17:00',
            'remind_at' => '2026-10-09 10:00:00',
            'reminder_sent_at' => '2026-10-09 10:00:05',
        ]);

        $this->patchJson("/api/v1/tasks/{$task->id}", ['due_date' => '2026-10-09', 'due_time' => '17:00', 'title' => 'Judul baru']);

        expect($task->fresh()->reminder_sent_at)->not->toBeNull();
    });

    it('clears the due time and the reminder when the due date is cleared', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-09',
            'due_time' => '17:00',
            'remind_at' => '2026-10-09 10:00:00',
        ]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['due_date' => null]);

        $response->assertOk()
            ->assertJsonPath('data.due_date', null)
            ->assertJsonPath('data.due_time', null)
            ->assertJsonPath('data.remind_at', null);
    });

    it('reminds at 08.00 once only the due time is cleared', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-09',
            'due_time' => '17:00',
            'remind_at' => '2026-10-09 10:00:00',
        ]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['due_time' => null]);

        $response->assertOk()->assertJsonPath('data.remind_at', '2026-10-09T01:00:00.000000Z');
    });

    it('returns 422 when a due time is set on a task without a due date', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['due_time' => '17:00']);

        $response->assertUnprocessable()->assertJsonPath('errors.due_time.0', 'Jam tenggat butuh tanggal tenggat.');
    });

    it('completes the task at the time the phone sent and records who did it', function () {
        $this->travelTo('2026-10-08 12:00:00');
        [$user, $partner, $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create(['created_by' => $user->id]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-08T18:30:00+07:00']);

        $response->assertOk()
            ->assertJsonPath('data.completed_at', '2026-10-08T11:30:00.000000Z')
            ->assertJsonPath('data.completed_by', $user->id);
    });

    it('pulls a completion time ahead of the server clock back to now', function () {
        $this->travelTo('2026-10-08 12:00:00');
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-08T13:00:00Z']);

        $response->assertOk()->assertJsonPath('data.completed_at', '2026-10-08T12:00:00.000000Z');
    });

    it('clears the completion when null is sent', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->completed()->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => null]);

        $response->assertOk()
            ->assertJsonPath('data.completed_at', null)
            ->assertJsonPath('data.completed_by', null);
    });

    it('replaces the assignees and bumps updated_at even when nothing else changed', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2026-10-08 10:00:00');
        [$user, $partner, $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create(['created_by' => $user->id, 'updated_by' => $user->id]);
        $this->travelTo('2026-10-08 11:00:00.750000');

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['assignee_ids' => [$partner->id]]);

        $response->assertOk()
            ->assertJsonPath('data.assignee_ids', [$partner->id])
            ->assertJsonPath('data.updated_at', '2026-10-08T11:00:00.750000Z');
        $this->assertDatabaseMissing('task_assignees', ['task_id' => $task->id, 'user_id' => $user->id]);
    });

    it('returns 422 for assignees the space does not allow', function (Closure $assigneeIds) {
        [$user, , $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create(['created_by' => $user->id]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['assignee_ids' => $assigneeIds()]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['assignee_ids']);
        $this->assertDatabaseHas('task_assignees', ['task_id' => $task->id, 'user_id' => $user->id]);
    })->with([
        'someone outside the space' => [fn () => [User::factory()->create()->id]],
        'an empty list' => [fn () => []],
    ]);

    it('queues a task_assigned push only for an assignee who was added', function () {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create(['created_by' => $user->id]);

        $this->patchJson("/api/v1/tasks/{$task->id}", ['assignee_ids' => [$user->id, $partner->id]]);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::TaskAssigned
            && $job->taskId === $task->id);
    });

    it('queues no task_assigned push when the assignees stay the same or the user takes the task over', function (Closure $assigneeIds) {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create(['created_by' => $partner->id]);

        $this->patchJson("/api/v1/tasks/{$task->id}", ['assignee_ids' => $assigneeIds($user, $partner)]);

        Queue::assertNotPushed(SendPushNotification::class);
    })->with([
        'the same assignee' => [fn (User $user, User $partner) => [$partner->id]],
        'the user themselves' => [fn (User $user) => [$user->id]],
    ]);

    it('queues a task_completed push for the creator when the partner completes the task', function () {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $user->update(['name' => 'Iqbal']);
        $task = Task::factory()->for(TaskList::factory()->for($space))->create(['title' => 'Beli galon', 'created_by' => $partner->id]);

        $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-08T10:15:30Z']);

        Queue::assertPushedTimes(SendPushNotification::class, 1);
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
            && $job->type === PushType::TaskCompleted
            && $job->title === 'Beli galon'
            && $job->body === 'Iqbal menyelesaikan tugas ini'
            && $job->taskId === $task->id);
    });

    it('queues no task_completed push when it would tell nobody anything new', function (Closure $arrange) {
        Queue::fake([SendPushNotification::class]);
        [$user, $partner, $space] = actingAsPartnered();
        $task = $arrange($user, $partner, TaskList::factory()->for($space)->create());

        $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-08T10:15:30Z']);

        Queue::assertNotPushed(SendPushNotification::class);
    })->with([
        'the creator completes their own task' => [fn (User $user, User $partner, TaskList $list) => Task::factory()->for($list)->create(['created_by' => $user->id])],
        'the task was already completed' => [fn (User $user, User $partner, TaskList $list) => Task::factory()->for($list)->completed()->create(['created_by' => $partner->id])],
    ]);

    it('still changes a task whose list was archived', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for(TaskList::factory()->for($user->personalSpace())->archived())->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Judul baru']);

        $response->assertOk()->assertJsonPath('data.title', 'Judul baru');
    });

    it('ignores read-only and unknown fields', function () {
        [$user, $partner] = actingAsPartnered();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", [
            'space_id' => newId(),
            'created_by' => $partner->id,
            'completed_by' => $partner->id,
            'remind_at' => '2030-01-01T00:00:00Z',
            'warna' => 'biru',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.space_id', $task->space_id)
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.completed_by', null)
            ->assertJsonPath('data.remind_at', null);
    });

    it('returns 404 with the not_found code for a task the user cannot reach', function (Closure $id) {
        [$user, $partner] = actingAsPartnered();

        $response = $this->patchJson('/api/v1/tasks/'.$id($partner), ['title' => 'Beli galon']);

        $response->assertNotFound()->assertExactJson([
            'message' => 'Data tidak ditemukan.',
            'code' => 'not_found',
            'current' => null,
        ]);
    })->with([
        'a task in the space of another couple' => [fn () => Task::factory()->create()->id],
        'a task in the personal space of the partner' => [fn (User $partner) => Task::factory()->for($partner->personalSpace()->taskLists()->sole())->create()->id],
        'an ID that was never created' => [fn () => newId()],
    ]);

    it('returns 410 with the resource_deleted code for a deleted task', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
        $task->delete();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Beli galon']);

        $response->assertGone()->assertJsonPath('code', 'resource_deleted');
    });

    it('returns 422 with the stored task as current for invalid values', function (array $payload, string $field) {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create(['title' => 'Beli galon']);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field])
            ->assertJsonPath('current.title', 'Beli galon');
    })->with([
        'an empty title' => [['title' => ''], 'title'],
        'a priority above 3' => [['priority' => 4], 'priority'],
        'a position too far from zero to store' => [['position' => '1e400'], 'position'],
        'a due date in another format' => [['due_date' => '09-10-2026'], 'due_date'],
        'a completion time without an offset' => [['completed_at' => '2026-10-08T10:15:30'], 'completed_at'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->patchJson('/api/v1/tasks/'.newId(), ['title' => 'Beli galon']);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('soft deletes the task and bumps its updated_at', function () {
        $this->travelTo('2026-10-08 10:00:00');
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
        $this->travelTo('2026-10-08 11:00:00.250000');

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($task);
        expect($task->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.250000Z');
    });

    it('deletes the comments and reactions on the task and bumps their updated_at, leaving the others alone', function () {
        $this->travelTo('2026-10-08 10:00:00');
        [, $partner, $space] = actingAsPartnered();
        $list = TaskList::factory()->for($space)->create();
        $task = Task::factory()->for($list)->create();
        $comment = Comment::factory()->on($task)->for($partner)->create();
        $reaction = Reaction::factory()->on($task)->for($partner)->create();
        $deletedBefore = Comment::factory()->on($task)->for($partner)->create();
        $deletedBefore->delete();
        $onAnotherTask = Comment::factory()->on(Task::factory()->for($list)->create())->for($partner)->create();
        $this->travelTo('2026-10-08 11:00:00.250000');

        $this->deleteJson("/api/v1/tasks/{$task->id}")->assertNoContent();

        $this->assertSoftDeleted($comment);
        $this->assertSoftDeleted($reaction);
        expect($comment->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.250000Z');
        expect($reaction->fresh()->updated_at->toJSON())->toBe('2026-10-08T11:00:00.250000Z');
        expect($deletedBefore->fresh()->updated_at->toJSON())->toBe('2026-10-08T10:00:00.000000Z');
        $this->assertNotSoftDeleted($onAnotherTask);
    });

    it('returns 204 again when the delete is repeated', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
        $task->delete();

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertNoContent();
    });

    it('returns 404 for a task in the personal space of the partner', function () {
        [, $partner] = actingAsPartnered();
        $task = Task::factory()->for($partner->personalSpace()->taskLists()->sole())->create();

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertNotFound()->assertJsonPath('code', 'not_found');
        $this->assertNotSoftDeleted($task);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/tasks/'.newId());

        $response->assertUnauthorized();
    });
});

describe('repeat', function () {
    it('stores the repeat rule of a new task, with an interval of 1 when none is sent', function () {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $user->personalSpace()->taskLists()->sole()->id,
            'title' => 'Bayar listrik',
            'due_date' => '2026-11-05',
            'repeat_frequency' => 'monthly',
            'position' => 1024,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.repeat_frequency', 'monthly')
            ->assertJsonPath('data.repeat_interval', 1)
            ->assertJsonPath('data.next_task_id', null)
            ->assertJsonPath('next_task', null);
    });

    it('returns 422 for a repeat rule without a due date or in the checklist of a trip', function (Closure $list, array $due, string $message) {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $list($user)->id,
            'title' => 'Bawa paspor',
            'repeat_frequency' => 'weekly',
            'position' => 1024,
            ...$due,
        ]);

        $response->assertUnprocessable()->assertJsonPath('errors.repeat_frequency.0', $message);
        $this->assertDatabaseMissing('tasks', ['title' => 'Bawa paspor']);
    })->with([
        'without a due date' => [fn (User $user) => $user->personalSpace()->taskLists()->sole(), [], 'Tugas berulang butuh tanggal tenggat.'],
        'in a trip checklist' => [
            fn (User $user) => TaskList::factory()->for($user->personalSpace())->create(['trip_id' => Trip::factory()->for($user->personalSpace())->create()->id]),
            ['due_date' => '2026-11-05'],
            'Checklist perjalanan tidak bisa berulang.',
        ],
    ]);

    it('returns 422 for an unknown frequency or an interval out of range', function (array $rule, string $field) {
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $user->personalSpace()->taskLists()->sole()->id,
            'title' => 'Bayar listrik',
            'due_date' => '2026-11-05',
            'position' => 1024,
            ...$rule,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
    })->with([
        'an unknown frequency' => [['repeat_frequency' => 'hourly'], 'repeat_frequency'],
        'an interval of 0' => [['repeat_frequency' => 'weekly', 'repeat_interval' => 0], 'repeat_interval'],
        'an interval of 100' => [['repeat_frequency' => 'weekly', 'repeat_interval' => 100], 'repeat_interval'],
    ]);

    it('creates the next occurrence when a recurring task is completed, and returns it with the task', function () {
        Queue::fake([SendPushNotification::class]);
        $this->travelTo('2026-10-17 10:00:00');
        [$user, $partner, $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create([
            'title' => 'Buang sampah',
            'notes' => 'Pisahkan yang organik',
            'priority' => 2,
            'position' => 2048,
            'due_date' => '2026-10-17',
            'due_time' => '17:00',
            'repeat_frequency' => 'weekly',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-17',
            'created_by' => $partner->id,
        ]);
        $task->assignees()->sync([$user->id, $partner->id]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-17T16:30:00+07:00']);

        $next = Task::query()->whereKeyNot($task->id)->sole();
        $response->assertOk()
            ->assertJsonPath('data.next_task_id', $next->id)
            ->assertJsonPath('next_task.id', $next->id)
            ->assertJsonPath('next_task.task_list_id', $task->task_list_id)
            ->assertJsonPath('next_task.title', 'Buang sampah')
            ->assertJsonPath('next_task.notes', 'Pisahkan yang organik')
            ->assertJsonPath('next_task.priority', 2)
            ->assertJsonPath('next_task.position', 2048)
            ->assertJsonPath('next_task.due_date', '2026-10-24')
            ->assertJsonPath('next_task.due_time', '17:00')
            ->assertJsonPath('next_task.repeat_frequency', 'weekly')
            ->assertJsonPath('next_task.repeat_interval', 1)
            ->assertJsonPath('next_task.assignee_ids', collect([$user->id, $partner->id])->sort()->values()->all())
            ->assertJsonPath('next_task.remind_at', '2026-10-24T10:00:00.000000Z')
            ->assertJsonPath('next_task.completed_at', null)
            ->assertJsonPath('next_task.created_by', $partner->id)
            ->assertJsonPath('next_task.updated_by', $user->id);
        Queue::assertNotPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->type === PushType::TaskAssigned);
    });

    it('sets the next due date after the later of the old due date and the day of completion, in the timezone of the space', function (string $completedAt, string $expected) {
        $this->travelTo('2026-10-26 00:00:00');
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'daily',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-20',
        ]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => $completedAt]);

        $response->assertOk()->assertJsonPath('next_task.due_date', $expected);
    })->with([
        'completed late, skipping the missed days' => ['2026-10-24T05:00:00Z', '2026-10-25'],
        'completed late at night, already the next day in Jakarta' => ['2026-10-24T18:00:00Z', '2026-10-26'],
        'completed early' => ['2026-10-18T05:00:00Z', '2026-10-21'],
    ]);

    it('keeps the day of the month after a shorter month', function () {
        $this->travelTo('2027-03-01 00:00:00');
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2027-01-31',
            'repeat_frequency' => 'monthly',
            'repeat_interval' => 1,
            'repeat_anchor' => '2027-01-31',
        ]);

        $february = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2027-01-31T05:00:00Z'])->json('next_task');
        $march = $this->patchJson("/api/v1/tasks/{$february['id']}", ['completed_at' => '2027-02-28T05:00:00Z'])->json('next_task');

        expect($february['due_date'])->toBe('2027-02-28')
            ->and($march['due_date'])->toBe('2027-03-31');
    });

    it('counts from the new due date once the user moves it', function () {
        $this->travelTo('2027-01-20 00:00:00');
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2027-01-31',
            'repeat_frequency' => 'monthly',
            'repeat_interval' => 1,
            'repeat_anchor' => '2027-01-31',
        ]);
        $this->patchJson("/api/v1/tasks/{$task->id}", ['due_date' => '2027-01-15']);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2027-01-15T05:00:00Z']);

        $response->assertOk()->assertJsonPath('next_task.due_date', '2027-02-15');
    });

    it('creates the next occurrence only once when the completion is sent again', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'daily',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-20',
        ]);
        $first = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T05:00:00Z']);

        $again = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T06:00:00Z']);

        $again->assertOk()->assertJsonPath('next_task.id', $first->json('next_task.id'));
        $this->assertDatabaseCount('tasks', 2);
    });

    it('takes back the untouched next occurrence when the completion is undone, and makes a new one when completed again', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'daily',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-20',
        ]);
        $firstNextId = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T05:00:00Z'])->json('next_task.id');

        $undone = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => null]);
        $completedAgain = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T06:00:00Z']);

        $undone->assertOk()->assertJsonPath('data.next_task_id', null)->assertJsonPath('next_task', null);
        $this->assertSoftDeleted('tasks', ['id' => $firstNextId]);
        $completedAgain->assertOk()->assertJsonPath('next_task.due_date', '2026-10-21');
        expect($completedAgain->json('next_task.id'))->not->toBe($firstNextId)
            ->and(Task::query()->whereKeyNot($task->id)->count())->toBe(1);
    });

    it('keeps a next occurrence someone changed when the completion is undone, and makes no second one', function () {
        $this->travelTo('2026-10-20 10:00:00');
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'daily',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-20',
        ]);
        $nextId = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T05:00:00Z'])->json('next_task.id');
        $this->travelTo('2026-10-20 10:05:00');
        $this->patchJson("/api/v1/tasks/{$nextId}", ['title' => 'Siram tanaman pagi']);

        $undone = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => null]);
        $completedAgain = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T06:00:00Z']);

        $undone->assertOk()->assertJsonPath('next_task.id', $nextId);
        $completedAgain->assertOk()->assertJsonPath('next_task.id', $nextId);
        $this->assertNotSoftDeleted('tasks', ['id' => $nextId]);
        $this->assertDatabaseCount('tasks', 2);
    });

    it('keeps a next occurrence someone commented on or reacted to when the completion is undone', function (string $discussion) {
        [, $partner, $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'daily',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-20',
        ]);
        $nextId = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T05:00:00Z'])->json('next_task.id');
        $discussion::factory()->on(Task::query()->findOrFail($nextId))->for($partner)->create();

        $undone = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => null]);

        $undone->assertOk()->assertJsonPath('data.next_task_id', $nextId)->assertJsonPath('next_task.id', $nextId);
        $this->assertNotSoftDeleted('tasks', ['id' => $nextId]);
    })->with([
        'a comment' => [Comment::class],
        'a reaction' => [Reaction::class],
    ]);

    it('still takes back the next occurrence when its only comment was deleted', function () {
        [, $partner, $space] = actingAsPartnered();
        $task = Task::factory()->for(TaskList::factory()->for($space))->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'daily',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-20',
        ]);
        $nextId = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T05:00:00Z'])->json('next_task.id');
        Comment::factory()->on(Task::query()->findOrFail($nextId))->for($partner)->create()->delete();

        $undone = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => null]);

        $undone->assertOk()->assertJsonPath('next_task', null);
        $this->assertSoftDeleted('tasks', ['id' => $nextId]);
    });

    it('stops repeating once the rule is cleared', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'weekly',
            'repeat_interval' => 2,
            'repeat_anchor' => '2026-10-20',
        ]);

        $cleared = $this->patchJson("/api/v1/tasks/{$task->id}", ['repeat_frequency' => null]);
        $completed = $this->patchJson("/api/v1/tasks/{$task->id}", ['completed_at' => '2026-10-20T05:00:00Z']);

        $cleared->assertOk()->assertJsonPath('data.repeat_frequency', null)->assertJsonPath('data.repeat_interval', null);
        $completed->assertOk()->assertJsonPath('next_task', null);
        $this->assertDatabaseCount('tasks', 1);
    });

    it('clears the repeat rule together with the due date', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'weekly',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-20',
        ]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['due_date' => null]);

        $response->assertOk()->assertJsonPath('data.repeat_frequency', null)->assertJsonPath('data.repeat_interval', null);
    });

    it('returns 422 with the stored task when a repeat rule is set on a task without a due date', function () {
        $user = actingAsApiUser();
        $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['repeat_frequency' => 'daily']);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.repeat_frequency.0', 'Tugas berulang butuh tanggal tenggat.')
            ->assertJsonPath('current.repeat_frequency', null);
    });

    it('returns 422 when a recurring task is moved into the checklist of a trip', function () {
        $user = actingAsApiUser();
        $space = $user->personalSpace();
        $task = Task::factory()->for($space->taskLists()->sole())->create([
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'weekly',
            'repeat_interval' => 1,
            'repeat_anchor' => '2026-10-20',
        ]);
        $checklist = TaskList::factory()->for($space)->create(['trip_id' => Trip::factory()->for($space)->create()->id]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['task_list_id' => $checklist->id]);

        $response->assertUnprocessable()->assertJsonPath('errors.task_list_id.0', 'Checklist perjalanan tidak bisa berulang.');
        expect($task->fresh()->task_list_id)->toBe($space->taskLists()->regular()->sole()->id);
    });

    it('creates the next occurrence of a recurring task that arrives already completed', function () {
        $this->travelTo('2026-10-21 00:00:00');
        $user = actingAsApiUser();

        $response = $this->putJson('/api/v1/tasks/'.newId(), [
            'task_list_id' => $user->personalSpace()->taskLists()->sole()->id,
            'title' => 'Minum vitamin',
            'due_date' => '2026-10-20',
            'repeat_frequency' => 'daily',
            'position' => 1024,
            'completed_at' => '2026-10-20T05:00:00Z',
        ]);

        $response->assertCreated()->assertJsonPath('next_task.due_date', '2026-10-21');
        expect($response->json('data.next_task_id'))->toBe($response->json('next_task.id'));
    });
});

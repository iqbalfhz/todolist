<?php

use App\Actions\NotifySpaceChanges;
use App\Jobs\SendSyncPush;
use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

/**
 * Get the sync pushes queued so far, as [recipient, space, gathered].
 *
 * @return list<array{0: string, 1: string, 2: bool}>
 */
function queuedSyncPushes(): array
{
    return Queue::pushed(SendSyncPush::class)
        ->map(fn (SendSyncPush $job): array => [$job->userId, $job->spaceId, $job->gathered])
        ->values()
        ->all();
}

it('queues a sync push for the partner, not the sender, when a request changes the shared space', function () {
    [, $partner, $space] = actingAsPartnered();
    $task = Task::factory()->for(TaskList::factory()->for($space))->create();
    Queue::fake([SendSyncPush::class]);

    $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Beli galon']);

    $response->assertOk();
    expect(queuedSyncPushes())->toBe([[$partner->id, $space->id, false]]);
});

it('queues a sync push when a request creates in the shared space', function () {
    [, $partner, $space] = actingAsPartnered();
    $list = TaskList::factory()->for($space)->create();
    Queue::fake([SendSyncPush::class]);

    $response = $this->putJson('/api/v1/tasks/'.newId(), ['task_list_id' => $list->id, 'title' => 'Beli galon', 'position' => 1024]);

    $response->assertCreated();
    expect(queuedSyncPushes())->toBe([[$partner->id, $space->id, false]]);
});

it('queues a sync push when a request deletes from the shared space', function () {
    [, $partner, $space] = actingAsPartnered();
    $task = Task::factory()->for(TaskList::factory()->for($space))->create();
    Queue::fake([SendSyncPush::class]);

    $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

    $response->assertNoContent();
    expect(queuedSyncPushes())->toBe([[$partner->id, $space->id, false]]);
});

it('queues a sync push when the sender changes the profile that the shared space shows', function () {
    [, $partner, $space] = actingAsPartnered();
    Queue::fake([SendSyncPush::class]);

    $response = $this->patchJson('/api/v1/me', ['name' => 'Iqbal']);

    $response->assertOk();
    expect(queuedSyncPushes())->toBe([[$partner->id, $space->id, false]]);
});

it('keeps the response of a saved change when its sync push cannot be queued', function () {
    [, , $space] = actingAsPartnered();
    $task = Task::factory()->for(TaskList::factory()->for($space))->create();
    $this->mock(NotifySpaceChanges::class, function (MockInterface $changes): void {
        $changes->shouldReceive('start', 'record');
        $changes->shouldReceive('send')->andThrow(new RuntimeException('Cache mati'));
    });

    $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Beli galon']);

    $response->assertOk()->assertJsonPath('data.title', 'Beli galon');
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Cache mati');
});

it('sends nothing for a change in the personal space', function () {
    [$user] = actingAsPartnered();
    $task = Task::factory()->for($user->personalSpace()->taskLists()->sole())->create();
    Queue::fake([SendSyncPush::class]);

    $response = $this->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Beli galon']);

    $response->assertOk();
    Queue::assertNotPushed(SendSyncPush::class);
});

it('sends nothing when someone registers, which creates only a personal space', function () {
    Queue::fake([SendSyncPush::class]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Iqbal',
        'email' => 'iqbal@contoh.test',
        'password' => 'rahasia-sekali',
        'device_name' => 'Pixel 8',
    ]);

    $response->assertCreated();
    Queue::assertNotPushed(SendSyncPush::class);
});

it('sends nothing when a repeated PUT changes nothing', function () {
    [, , $space] = actingAsPartnered();
    $task = Task::factory()->for(TaskList::factory()->for($space))->create();
    Queue::fake([SendSyncPush::class]);

    $response = $this->putJson("/api/v1/tasks/{$task->id}", [
        'task_list_id' => $task->task_list_id,
        'title' => $task->title,
        'position' => $task->position,
    ]);

    $response->assertOk();
    Queue::assertNotPushed(SendSyncPush::class);
});

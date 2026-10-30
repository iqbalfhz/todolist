<?php

use App\Actions\SendDueReminders;
use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('queues a task_reminder push for the assignee when remind_at arrives', function () {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    $list = TaskList::factory()->for(sharedSpaceBetween($owner, $partner))->create();
    $task = Task::factory()->for($list)->hasAttached($partner, [], 'assignees')->create([
        'title' => 'Beli galon',
        'due_date' => '2026-10-09',
        'due_time' => '17:00',
        'remind_at' => '2026-10-09 10:00:00',
    ]);
    $this->travelTo('2026-10-09 10:00:20');

    $result = app(SendDueReminders::class)->handle();

    expect($result)->toBe(['sent' => 1, 'late' => 0]);
    Queue::assertPushedTimes(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
        && $job->type === PushType::TaskReminder
        && $job->title === 'Beli galon'
        && $job->body === 'Tenggat hari ini, 17.00'
        && $job->spaceId === $list->space_id
        && $job->taskId === $task->id
        && $job->savingsGoalId === null);
});

it('leaves the time out of the reminder of a task that is due all day', function () {
    Queue::fake([SendPushNotification::class]);
    Task::factory()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']);
    $this->travelTo('2026-10-09 01:00:00');

    app(SendDueReminders::class)->handle();

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->body === 'Tenggat hari ini');
});

it('reminds both partners of a task they share', function () {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    Task::factory()
        ->for(TaskList::factory()->for(sharedSpaceBetween($owner, $partner)))
        ->hasAttached([$owner, $partner], [], 'assignees')
        ->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']);
    $this->travelTo('2026-10-09 01:00:00');

    app(SendDueReminders::class)->handle();

    Queue::assertPushedTimes(SendPushNotification::class, 2);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $owner->id);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id);
});

it('marks the reminder as sent without changing updated_at', function () {
    Queue::fake([SendPushNotification::class]);
    $this->travelTo('2026-10-08 09:00:00');
    $task = Task::factory()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']);
    $this->travelTo('2026-10-09 01:00:20.500000');

    app(SendDueReminders::class)->handle();

    $task->refresh();
    expect($task->reminder_sent_at->toJSON())->toBe('2026-10-09T01:00:20.500000Z')
        ->and($task->updated_at->toJSON())->toBe('2026-10-08T09:00:00.000000Z');
});

it('reminds a task only once', function () {
    Queue::fake([SendPushNotification::class]);
    Task::factory()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']);
    $this->travelTo('2026-10-09 01:00:00');
    app(SendDueReminders::class)->handle();
    $this->travelTo('2026-10-09 01:01:00');

    $result = app(SendDueReminders::class)->handle();

    expect($result)->toBe(['sent' => 0, 'late' => 0]);
    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

it('queues no push and keeps the reminder open', function (Closure $task) {
    Queue::fake([SendPushNotification::class]);
    $task = $task();
    $this->travelTo('2026-10-09 01:00:00');

    $result = app(SendDueReminders::class)->handle();

    expect($result)->toBe(['sent' => 0, 'late' => 0])
        ->and($task->fresh()->reminder_sent_at)->toBeNull();
    Queue::assertNotPushed(SendPushNotification::class);
})->with([
    'for a completed task' => [fn () => Task::factory()->completed()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00'])],
    'for a deleted task' => [fn () => tap(Task::factory()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']))->delete()],
    'for a task in an archived list' => [fn () => Task::factory()->for(TaskList::factory()->archived())->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00'])],
    'for a task whose reminder is a second away' => [fn () => Task::factory()->create(['due_date' => '2026-10-09', 'due_time' => '08:00', 'remind_at' => '2026-10-09 01:00:01'])],
    'for a task without a due date' => [fn () => Task::factory()->create()],
]);

it('still sends a reminder that is exactly 30 minutes late', function () {
    Queue::fake([SendPushNotification::class]);
    Task::factory()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']);
    $this->travelTo('2026-10-09 01:30:00');

    $result = app(SendDueReminders::class)->handle();

    expect($result)->toBe(['sent' => 1, 'late' => 0]);
    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

it('marks a reminder that is more than 30 minutes late as sent without a push or a new updated_at', function (Closure $task) {
    Queue::fake([SendPushNotification::class]);
    $this->travelTo('2026-10-08 09:00:00');
    $task = $task();
    $this->travelTo('2026-10-09 01:30:00.000001');

    $result = app(SendDueReminders::class)->handle();

    $task->refresh();
    expect($result)->toBe(['sent' => 0, 'late' => 1])
        ->and($task->reminder_sent_at->toJSON())->toBe('2026-10-09T01:30:00.000001Z')
        ->and($task->updated_at->toJSON())->toBe('2026-10-08T09:00:00.000000Z');
    Queue::assertNotPushed(SendPushNotification::class);
})->with([
    'for a task that is still open' => [fn () => Task::factory()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00'])],
    'for a completed task' => [fn () => Task::factory()->completed()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00'])],
    'for a deleted task' => [fn () => tap(Task::factory()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']))->delete()],
    'for a task in an archived list' => [fn () => Task::factory()->for(TaskList::factory()->archived())->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00'])],
]);

it('sends no reminder for a task in a shared space that was closed', function () {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->create();
    $space = sharedSpaceBetween($owner, User::factory()->create());
    $task = Task::factory()
        ->for(TaskList::factory()->for($space))
        ->hasAttached($owner, [], 'assignees')
        ->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']);
    $space->delete();
    $this->travelTo('2026-10-09 01:00:00');

    $result = app(SendDueReminders::class)->handle();

    expect($result)->toBe(['sent' => 0, 'late' => 0]);
    expect($task->fresh()->reminder_sent_at)->toBeNull();
    Queue::assertNothingPushed();
});

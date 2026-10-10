<?php

use App\Jobs\RecalculateSpaceReminders;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;

it('recalculates the reminders of the space for its new timezone', function () {
    $space = Space::factory()->create(['timezone' => 'Asia/Jakarta']);
    $list = TaskList::factory()->for($space)->create();
    $task = Task::factory()->for($list)->create([
        'due_date' => '2026-10-09',
        'due_time' => '17:00',
        'remind_at' => '2026-10-09 10:00:00',
    ]);
    $space->update(['timezone' => 'Asia/Makassar']);

    RecalculateSpaceReminders::dispatchSync($space->id);

    expect($task->fresh()->remind_at->toJSON())->toBe('2026-10-09T09:00:00.000000Z');
});

it('leaves tasks without a due date and tasks of other spaces alone', function () {
    $space = Space::factory()->create(['timezone' => 'Asia/Makassar']);
    $undatedTask = Task::factory()->for(TaskList::factory()->for($space))->create();
    $otherSpaceTask = Task::factory()->create([
        'due_date' => '2026-10-09',
        'due_time' => '17:00',
        'remind_at' => '2026-10-09 10:00:00',
    ]);

    RecalculateSpaceReminders::dispatchSync($space->id);

    expect($undatedTask->fresh()->remind_at)->toBeNull()
        ->and($otherSpaceTask->fresh()->remind_at->toJSON())->toBe('2026-10-09T10:00:00.000000Z');
});

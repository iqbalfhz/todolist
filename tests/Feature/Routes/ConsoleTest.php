<?php

use App\Jobs\SendPushNotification;
use App\Models\Task;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;

it('sends the due reminders and reports how many', function () {
    Queue::fake([SendPushNotification::class]);
    Task::factory()->create(['due_date' => '2026-10-09', 'remind_at' => '2026-10-09 01:00:00']);
    Task::factory()->create(['due_date' => '2026-10-08', 'remind_at' => '2026-10-08 01:00:00']);
    $this->travelTo('2026-10-09 01:00:00');

    $this->artisan('reminders:send')
        ->expectsOutput('Reminders sent: 1. Too late to send: 1.')
        ->assertSuccessful();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

// No request or command reaches the schedule, so the test reads the registered event itself.
it('runs reminders:send every minute without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->sole(fn (Event $event) => str_contains($event->command, 'reminders:send'));

    expect($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(5);
});

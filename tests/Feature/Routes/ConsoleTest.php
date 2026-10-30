<?php

use App\Jobs\SendPushNotification;
use App\Models\SavingsGoal;
use App\Models\SpecialDate;
use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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

it('sends the special date reminders that are due and reports how many', function () {
    Queue::fake([SendPushNotification::class]);
    SpecialDate::factory()->create(['date' => '2023-10-29']);
    $this->travelTo('2026-10-29 01:00:00');

    $this->artisan('special-dates:remind')
        ->expectsOutput('Special date reminders sent: 1.')
        ->assertSuccessful();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

it('runs special-dates:remind every minute without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->sole(fn (Event $event) => str_contains($event->command, 'special-dates:remind'));

    expect($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(5);
});

it('sends the trip reminders that are due and reports how many', function () {
    Queue::fake([SendPushNotification::class]);
    Trip::factory()->create(['start_date' => '2026-10-29']);
    $this->travelTo('2026-10-22 01:00:00');

    $this->artisan('trips:remind')
        ->expectsOutput('Trip reminders sent: 1.')
        ->assertSuccessful();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

it('runs trips:remind every minute without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->sole(fn (Event $event) => str_contains($event->command, 'trips:remind'));

    expect($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(5);
});

it('sends the deposit reminders that are due and reports how many', function () {
    Queue::fake([SendPushNotification::class]);
    SavingsGoal::factory()->create(['reminder_frequency' => 'weekly', 'reminder_day' => 4]);
    $this->travelTo('2026-10-22 01:00:00');

    $this->artisan('savings:remind')
        ->expectsOutput('Savings reminders sent: 1.')
        ->assertSuccessful();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

it('runs savings:remind every minute without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->sole(fn (Event $event) => str_contains($event->command, 'savings:remind'));

    expect($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(5);
});

it('deletes the closed shared spaces whose 30 days ran out and reports how many', function () {
    Storage::fake('r2');
    $this->travelTo('2026-09-01 08:00:00');
    $space = sharedSpaceBetween(User::factory()->create(), User::factory()->create());
    $space->delete();
    $this->travelTo('2026-10-02 08:00:00');

    $this->artisan('spaces:purge')
        ->expectsOutput('Closed shared spaces deleted for good: 1.')
        ->assertSuccessful();

    $this->assertModelMissing($space);
});

it('runs spaces:purge once a day without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->sole(fn (Event $event) => str_contains($event->command, 'spaces:purge'));

    expect($event->expression)->toBe('0 0 * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(60);
});

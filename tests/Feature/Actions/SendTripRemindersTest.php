<?php

use App\Actions\SendTripReminders;
use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

// Spaces are in Asia/Jakarta unless a test says otherwise, so 08.00 there is 01.00 UTC.

it('queues a reminder for both members at 08.00 7 days and 1 day before the trip starts', function (string $now, string $body) {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    $space = sharedSpaceBetween($owner, $partner);
    $trip = Trip::factory()->for($space)->create(['title' => 'Liburan ke Bali', 'start_date' => '2026-10-29']);
    $this->travelTo($now);

    $sent = app(SendTripReminders::class)->handle();

    expect($sent)->toBe(1);
    Queue::assertPushedTimes(SendPushNotification::class, 2);
    foreach ([$owner, $partner] as $member) {
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $member->id
            && $job->type === PushType::TripReminder
            && $job->title === 'Perjalanan'
            && $job->body === $body
            && $job->spaceId === $space->id
            && $job->tripId === $trip->id);
    }
})->with([
    '7 days before' => ['2026-10-22 01:00:00', 'Liburan ke Bali 7 hari lagi'],
    '1 day before' => ['2026-10-28 01:00:00', 'Liburan ke Bali besok'],
]);

it('sends nothing on the day itself or on other days', function (string $now) {
    Queue::fake([SendPushNotification::class]);
    Trip::factory()->create(['start_date' => '2026-10-29']);
    $this->travelTo($now);

    $sent = app(SendTripReminders::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'on the day' => ['2026-10-29 01:00:00'],
    '3 days before' => ['2026-10-26 01:00:00'],
]);

it('adds the progress of the checklist once it has items', function () {
    Queue::fake([SendPushNotification::class]);
    $trip = Trip::factory()->create(['title' => 'Liburan ke Bali', 'start_date' => '2026-10-29']);
    $checklist = TaskList::factory()->for($trip->space)->create(['trip_id' => $trip->id]);
    Task::factory()->for($checklist)->count(2)->create(['completed_at' => now()]);
    Task::factory()->for($checklist)->create();
    Task::factory()->for($checklist)->create()->delete();
    $this->travelTo('2026-10-22 01:00:00');

    app(SendTripReminders::class)->handle();

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->body === 'Liburan ke Bali 7 hari lagi. Persiapan 2 dari 3 beres.');
});

it('sends nothing for a trip without a start date or a deleted trip', function (Closure $trip) {
    Queue::fake([SendPushNotification::class]);
    $trip();
    $this->travelTo('2026-10-22 01:00:00');

    $sent = app(SendTripReminders::class)->handle();

    expect($sent)->toBe(0);
})->with([
    'no start date' => [fn () => Trip::factory()->create(['start_date' => null, 'end_date' => null])],
    'a deleted trip' => [fn () => Trip::factory()->create(['start_date' => '2026-10-29'])->delete()],
]);

it('sends each reminder only once, however often it runs', function () {
    Queue::fake([SendPushNotification::class]);
    Trip::factory()->create(['start_date' => '2026-10-29']);
    $this->travelTo('2026-10-22 01:00:00');
    app(SendTripReminders::class)->handle();
    $this->travelTo('2026-10-22 01:01:00');

    $sent = app(SendTripReminders::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

it('reminds again for the new date of a trip that was moved', function () {
    Queue::fake([SendPushNotification::class]);
    $trip = Trip::factory()->create(['start_date' => '2026-10-29']);
    $this->travelTo('2026-10-22 01:00:00');
    app(SendTripReminders::class)->handle();
    $trip->update(['start_date' => '2026-10-23', 'end_date' => null]);

    $sent = app(SendTripReminders::class)->handle();

    expect($sent)->toBe(1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => str_ends_with($job->body, 'besok'));
});

it('reminds only the owner of a trip in the personal space', function () {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->withPersonalSpace()->create();
    sharedSpaceBetween($owner, User::factory()->create());
    Trip::factory()->for($owner->personalSpace())->create(['start_date' => '2026-10-29']);
    $this->travelTo('2026-10-22 01:00:00');

    app(SendTripReminders::class)->handle();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $owner->id);
});

it('sends nothing before 08.00 or after 08.30', function (string $now) {
    Queue::fake([SendPushNotification::class]);
    Trip::factory()->create(['start_date' => '2026-10-29']);
    $this->travelTo($now);

    $sent = app(SendTripReminders::class)->handle();

    expect($sent)->toBe(0);
})->with([
    '07.59' => ['2026-10-22 00:59:59'],
    '08.31' => ['2026-10-22 01:31:00'],
]);

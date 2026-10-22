<?php

use App\Actions\SendSavingsReminders;
use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

// Spaces are in Asia/Jakarta unless a test says otherwise, so 08.00 there is 01.00 UTC.
// 22 October 2026 is a Thursday, ISO weekday 4.

it('queues a reminder for both members at 08.00 on the chosen weekday, with the share already saved', function () {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    $space = sharedSpaceBetween($owner, $partner);
    $goal = SavingsGoal::factory()->for($space)->create([
        'name' => 'Dana Liburan',
        'target_amount' => 3_000_000,
        'reminder_frequency' => 'weekly',
        'reminder_day' => 4,
    ]);
    SavingsEntry::factory()->for($goal)->create(['amount' => 1_000_000]);
    $this->travelTo('2026-10-22 01:00:00');

    $sent = app(SendSavingsReminders::class)->handle();

    expect($sent)->toBe(1);
    Queue::assertPushedTimes(SendPushNotification::class, 2);
    foreach ([$owner, $partner] as $member) {
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $member->id
            && $job->type === PushType::SavingsReminder
            && $job->title === 'Dana Liburan'
            && $job->body === 'Waktunya menabung. Terkumpul 33% dari target.'
            && $job->spaceId === $space->id
            && $job->savingsGoalId === $goal->id);
    }
});

it('sends on the chosen day of the month', function (string $now, int $expected) {
    Queue::fake([SendPushNotification::class]);
    SavingsGoal::factory()->create(['reminder_frequency' => 'monthly', 'reminder_day' => 25]);
    $this->travelTo($now);

    $sent = app(SendSavingsReminders::class)->handle();

    expect($sent)->toBe($expected);
})->with([
    'the 25th' => ['2026-10-25 01:00:00', 1],
    'the 24th' => ['2026-10-24 01:00:00', 0],
]);

it('sends nothing on another weekday or for a goal without a schedule', function (array $schedule) {
    Queue::fake([SendPushNotification::class]);
    SavingsGoal::factory()->create($schedule);
    $this->travelTo('2026-10-22 01:00:00');

    $sent = app(SendSavingsReminders::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'another weekday' => [['reminder_frequency' => 'weekly', 'reminder_day' => 5]],
    'no schedule' => [['reminder_frequency' => null, 'reminder_day' => null]],
]);

it('leaves achieved, archived, and deleted goals alone', function (Closure $goal) {
    Queue::fake([SendPushNotification::class]);
    $goal();
    $this->travelTo('2026-10-22 01:00:00');

    $sent = app(SendSavingsReminders::class)->handle();

    expect($sent)->toBe(0);
})->with([
    'achieved' => [fn () => SavingsGoal::factory()->create(['reminder_frequency' => 'weekly', 'reminder_day' => 4, 'achieved_at' => now()])],
    'archived' => [fn () => SavingsGoal::factory()->archived()->create(['reminder_frequency' => 'weekly', 'reminder_day' => 4])],
    'deleted' => [fn () => SavingsGoal::factory()->create(['reminder_frequency' => 'weekly', 'reminder_day' => 4])->delete()],
]);

it('sends each reminder only once a day, however often it runs', function () {
    Queue::fake([SendPushNotification::class]);
    SavingsGoal::factory()->create(['reminder_frequency' => 'weekly', 'reminder_day' => 4]);
    $this->travelTo('2026-10-22 01:00:00');
    app(SendSavingsReminders::class)->handle();
    $this->travelTo('2026-10-22 01:01:00');

    $sent = app(SendSavingsReminders::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

it('uses the weekday in the timezone of the space', function () {
    Queue::fake([SendPushNotification::class]);
    $space = Space::factory()->create(['timezone' => 'Asia/Tokyo']);
    SavingsGoal::factory()->for($space)->create(['reminder_frequency' => 'weekly', 'reminder_day' => 4]);
    $this->travelTo('2026-10-21 23:00:00');

    $sent = app(SendSavingsReminders::class)->handle();

    expect($sent)->toBe(1);
});

it('sends nothing before 08.00 or after 08.30', function (string $now) {
    Queue::fake([SendPushNotification::class]);
    SavingsGoal::factory()->create(['reminder_frequency' => 'weekly', 'reminder_day' => 4]);
    $this->travelTo($now);

    $sent = app(SendSavingsReminders::class)->handle();

    expect($sent)->toBe(0);
})->with([
    '07.59' => ['2026-10-22 00:59:59'],
    '08.31' => ['2026-10-22 01:31:00'],
]);

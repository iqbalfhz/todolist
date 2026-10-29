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

it('takes withdrawals off the share already saved, and never says less than 0%', function (int $deposited, int $withdrawn, string $body) {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->withPersonalSpace()->create();
    $goal = SavingsGoal::factory()->for($owner->personalSpace())->create([
        'target_amount' => 3_000_000,
        'reminder_frequency' => 'weekly',
        'reminder_day' => 4,
    ]);
    SavingsEntry::factory()->for($goal)->create(['amount' => $deposited]);
    SavingsEntry::factory()->for($goal)->withdrawal()->create(['amount' => $withdrawn]);
    $this->travelTo('2026-10-22 01:00:00');

    app(SendSavingsReminders::class)->handle();

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->body === $body);
})->with([
    'a third left after a withdrawal' => [1_500_000, 500_000, 'Waktunya menabung. Terkumpul 33% dari target.'],
    'more taken out than saved' => [100_000, 400_000, 'Waktunya menabung. Terkumpul 0% dari target.'],
]);

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

it('reminds a day the month does not have on its last day', function (int $day, string $now, int $expected) {
    Queue::fake([SendPushNotification::class]);
    SavingsGoal::factory()->create(['reminder_frequency' => 'monthly', 'reminder_day' => $day]);
    $this->travelTo($now);

    $sent = app(SendSavingsReminders::class)->handle();

    expect($sent)->toBe($expected);
})->with([
    'day 31 on 30 November' => [31, '2026-11-30 01:00:00', 1],
    'day 31 on 30 October, a day before the real one' => [31, '2026-10-30 01:00:00', 0],
    'day 30 on 28 February 2027' => [30, '2027-02-28 01:00:00', 1],
    'day 30 on 28 February 2028, a leap year' => [30, '2028-02-28 01:00:00', 0],
    'day 30 on 29 February 2028' => [30, '2028-02-29 01:00:00', 1],
    'day 15 on 30 November' => [15, '2026-11-30 01:00:00', 0],
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

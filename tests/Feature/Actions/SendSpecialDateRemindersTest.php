<?php

use App\Actions\SendSpecialDateReminders;
use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Space;
use App\Models\SpecialDate;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

// Spaces are in Asia/Jakarta unless a test says otherwise, so 08.00 there is 01.00 UTC.

it('queues a reminder for both members at 08.00 7 days before, 1 day before, and on the day', function (string $now, string $body) {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    $space = sharedSpaceBetween($owner, $partner);
    $specialDate = SpecialDate::factory()->for($space)->create(['title' => 'Hari jadi kita', 'date' => '2023-10-29']);
    $this->travelTo($now);

    $sent = app(SendSpecialDateReminders::class)->handle();

    expect($sent)->toBe(1);
    Queue::assertPushedTimes(SendPushNotification::class, 2);
    foreach ([$owner, $partner] as $member) {
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $member->id
            && $job->type === PushType::SpecialDateReminder
            && $job->title === 'Tanggal penting'
            && $job->body === $body
            && $job->spaceId === $space->id
            && $job->specialDateId === $specialDate->id
            && $job->memberId === null);
    }
})->with([
    '7 days before' => ['2026-10-22 01:00:00', 'Hari jadi kita 7 hari lagi'],
    '1 day before' => ['2026-10-28 01:00:00', 'Hari jadi kita besok'],
    'on the day' => ['2026-10-29 01:00:00', 'Hari jadi kita hari ini'],
]);

it('sends nothing on the other days', function () {
    Queue::fake([SendPushNotification::class]);
    SpecialDate::factory()->create(['date' => '2023-10-29']);
    $this->travelTo('2026-10-25 01:00:00');

    $sent = app(SendSpecialDateReminders::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertNothingPushed();
});

it('sends until 08.30 and nothing before 08.00 or after 08.30', function (string $now, int $expected) {
    Queue::fake([SendPushNotification::class]);
    SpecialDate::factory()->create(['date' => '2023-10-29']);
    $this->travelTo($now);

    $sent = app(SendSpecialDateReminders::class)->handle();

    expect($sent)->toBe($expected);
})->with([
    '07.59' => ['2026-10-29 00:59:59', 0],
    '08.30' => ['2026-10-29 01:30:00', 1],
    '08.31' => ['2026-10-29 01:31:00', 0],
]);

it('sends each reminder only once, however often it runs', function () {
    Queue::fake([SendPushNotification::class]);
    SpecialDate::factory()->create(['date' => '2023-10-29']);
    $this->travelTo('2026-10-29 01:00:00');
    app(SendSpecialDateReminders::class)->handle();
    $this->travelTo('2026-10-29 01:01:00');

    $sent = app(SendSpecialDateReminders::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertPushedTimes(SendPushNotification::class, 1);
});

it('sends at 08.00 in the timezone of the space', function () {
    Queue::fake([SendPushNotification::class]);
    $space = Space::factory()->create(['timezone' => 'Asia/Tokyo']);
    SpecialDate::factory()->for($space)->create(['title' => 'Hari jadi kita', 'date' => '2023-10-29']);
    $this->travelTo('2026-10-28 23:00:00');

    app(SendSpecialDateReminders::class)->handle();

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->body === 'Hari jadi kita hari ini');
});

it('reminds only the owner of a date in the personal space', function () {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->withPersonalSpace()->create();
    sharedSpaceBetween($owner, User::factory()->create());
    SpecialDate::factory()->for($owner->personalSpace())->create(['date' => '2023-10-29']);
    $this->travelTo('2026-10-29 01:00:00');

    app(SendSpecialDateReminders::class)->handle();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $owner->id);
});

it('leaves deleted dates alone', function () {
    Queue::fake([SendPushNotification::class]);
    SpecialDate::factory()->create(['date' => '2023-10-29'])->delete();
    $this->travelTo('2026-10-29 01:00:00');

    $sent = app(SendSpecialDateReminders::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertNothingPushed();
});

it('reminds of a date without a year', function () {
    Queue::fake([SendPushNotification::class]);
    SpecialDate::factory()->create(['date' => '--10-29']);
    $this->travelTo('2026-10-29 01:00:00');

    $sent = app(SendSpecialDateReminders::class)->handle();

    expect($sent)->toBe(1);
});

it('reminds of 29 February on 28 February in a year that is not a leap year', function (string $now, string $body) {
    Queue::fake([SendPushNotification::class]);
    SpecialDate::factory()->create(['title' => 'Ulang tahun Ibu', 'date' => '--02-29']);
    $this->travelTo($now);

    app(SendSpecialDateReminders::class)->handle();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->body === $body);
})->with([
    '28 February 2027' => ['2027-02-28 01:00:00', 'Ulang tahun Ibu hari ini'],
    '28 February 2028, a leap year' => ['2028-02-28 01:00:00', 'Ulang tahun Ibu besok'],
    '29 February 2028' => ['2028-02-29 01:00:00', 'Ulang tahun Ibu hari ini'],
]);

it('reminds only the partner of a member\'s birthday', function () {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->create(['name' => 'Iqbal', 'birth_date' => '--10-29']);
    $partner = User::factory()->create();
    $space = sharedSpaceBetween($owner, $partner);
    $this->travelTo('2026-10-22 01:00:00');

    $sent = app(SendSpecialDateReminders::class)->handle();

    expect($sent)->toBe(1);
    Queue::assertPushedTimes(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
        && $job->type === PushType::SpecialDateReminder
        && $job->title === 'Tanggal penting'
        && $job->body === 'Ulang tahun Iqbal 7 hari lagi'
        && $job->spaceId === $space->id
        && $job->memberId === $owner->id
        && $job->specialDateId === null);
});

it('reminds nobody of the birthday of an account without a shared space', function () {
    Queue::fake([SendPushNotification::class]);
    User::factory()->withPersonalSpace()->create(['birth_date' => '1995-10-29']);
    $this->travelTo('2026-10-29 01:00:00');

    $sent = app(SendSpecialDateReminders::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertNothingPushed();
});

<?php

use App\Actions\SendMemoryFlashbacks;
use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Memory;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

// Spaces are in Asia/Jakarta unless a test says otherwise, so 08.00 there is 01.00 UTC.

it('queues one reminder for both members at 08.00 of a memory from this day a year ago', function () {
    Queue::fake([SendPushNotification::class]);
    $owner = User::factory()->create();
    $partner = User::factory()->create();
    $space = sharedSpaceBetween($owner, $partner);
    $memory = Memory::factory()->for($space)->create(['title' => 'Piknik di Kebun Raya', 'visited_on' => '2025-11-02']);
    $this->travelTo('2026-11-02 01:00:00');

    $sent = app(SendMemoryFlashbacks::class)->handle();

    expect($sent)->toBe(1);
    Queue::assertPushedTimes(SendPushNotification::class, 2);
    foreach ([$owner, $partner] as $member) {
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $member->id
            && $job->type === PushType::MemoryFlashback
            && $job->title === 'Hari ini setahun lalu'
            && $job->body === 'Piknik di Kebun Raya'
            && $job->spaceId === $space->id
            && $job->memoryId === $memory->id);
    }
});

it('reminds every space that has a memory from this day, each of its own', function () {
    Queue::fake([SendPushNotification::class]);
    $user = User::factory()->withPersonalSpace()->create();
    $sharedSpace = sharedSpaceBetween($user, User::factory()->create());
    $personal = Memory::factory()->for($user->personalSpace())->create(['visited_on' => '2025-11-02']);
    $shared = Memory::factory()->for($sharedSpace)->create(['visited_on' => '2025-11-02']);
    $this->travelTo('2026-11-02 01:00:00');

    $sent = app(SendMemoryFlashbacks::class)->handle();

    expect($sent)->toBe(2);
    Queue::assertPushedTimes(SendPushNotification::class, 3);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->memoryId === $personal->id && $job->userId === $user->id);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->memoryId === $shared->id && $job->userId === $user->id);
});

it('reminds only the owner of a personal space', function () {
    Queue::fake([SendPushNotification::class]);
    $user = User::factory()->withPersonalSpace()->create();
    Memory::factory()->for($user->personalSpace())->create(['visited_on' => '2025-11-02']);
    $this->travelTo('2026-11-02 01:00:00');

    app(SendMemoryFlashbacks::class)->handle();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $user->id);
});

it('shows the memory from the latest year, the first made in that year, and counts the others', function () {
    Queue::fake([SendPushNotification::class]);
    $space = User::factory()->withPersonalSpace()->create()->personalSpace();
    Memory::factory()->for($space)->create(['title' => 'Wisuda', 'visited_on' => '2022-11-02']);
    $this->travelTo('2026-10-03 00:00:00');
    Memory::factory()->for($space)->create(['title' => 'Makan malam', 'visited_on' => '2023-11-02']);
    $this->travelTo('2026-10-02 00:00:00');
    $shown = Memory::factory()->for($space)->create(['title' => 'Liburan ke Bali', 'visited_on' => '2023-11-02']);
    $this->travelTo('2026-11-02 01:00:00');

    app(SendMemoryFlashbacks::class)->handle();

    Queue::assertPushedTimes(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->title === 'Hari ini 3 tahun lalu'
        && $job->body === 'Liburan ke Bali dan 2 kenangan lain'
        && $job->memoryId === $shown->id);
});

it('sends nothing for memories from this year, from other days, or deleted', function (Closure $create) {
    Queue::fake([SendPushNotification::class]);
    $create(User::factory()->withPersonalSpace()->create()->personalSpace());
    $this->travelTo('2026-11-02 01:00:00');

    $sent = app(SendMemoryFlashbacks::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'this day this year' => [fn (Space $space) => Memory::factory()->for($space)->create(['visited_on' => '2026-11-02'])],
    'the day before' => [fn (Space $space) => Memory::factory()->for($space)->create(['visited_on' => '2025-11-01'])],
    'the day after' => [fn (Space $space) => Memory::factory()->for($space)->create(['visited_on' => '2025-11-03'])],
    'the same day of another month' => [fn (Space $space) => Memory::factory()->for($space)->create(['visited_on' => '2025-10-02'])],
    'deleted' => [fn (Space $space) => Memory::factory()->for($space)->create(['visited_on' => '2025-11-02'])->delete()],
]);

it('sends nothing for a closed shared space', function () {
    Queue::fake([SendPushNotification::class]);
    $space = sharedSpaceBetween(User::factory()->create(), User::factory()->create());
    Memory::factory()->for($space)->create(['visited_on' => '2025-11-02']);
    $space->delete();
    $this->travelTo('2026-11-02 01:00:00');

    $sent = app(SendMemoryFlashbacks::class)->handle();

    expect($sent)->toBe(0);
    Queue::assertNothingPushed();
});

it('reminds of a memory from 29 February on 28 February in a year that is not a leap year', function (string $now, ?string $title) {
    Queue::fake([SendPushNotification::class]);
    Memory::factory()->create(['visited_on' => '2024-02-29']);
    $this->travelTo($now);

    app(SendMemoryFlashbacks::class)->handle();

    if ($title === null) {
        Queue::assertNothingPushed();
    } else {
        Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->title === $title);
    }
})->with([
    '28 February of a common year' => ['2027-02-28 01:00:00', 'Hari ini 3 tahun lalu'],
    '28 February of a leap year' => ['2028-02-28 01:00:00', null],
    '29 February of a leap year' => ['2028-02-29 01:00:00', 'Hari ini 4 tahun lalu'],
]);

it('sends the reminder once a day, and only from 08.00 to 08.30', function (array $runs, int $pushes) {
    Queue::fake([SendPushNotification::class]);
    Memory::factory()->create(['visited_on' => '2025-11-02']);

    foreach ($runs as $now) {
        $this->travelTo($now);
        app(SendMemoryFlashbacks::class)->handle();
    }

    Queue::assertPushedTimes(SendPushNotification::class, $pushes);
})->with([
    'twice in the window' => [['2026-11-02 01:00:00', '2026-11-02 01:10:00'], 1],
    'before 08.00' => [['2026-11-02 00:59:00'], 0],
    'after 08.30' => [['2026-11-02 01:31:00'], 0],
]);

it('takes this day in the timezone of the space', function () {
    Queue::fake([SendPushNotification::class]);
    $space = Space::factory()->create(['timezone' => 'Asia/Tokyo']);
    Memory::factory()->for($space)->create(['visited_on' => '2025-11-02']);
    $this->travelTo('2026-11-01 23:00:00');

    $sent = app(SendMemoryFlashbacks::class)->handle();

    expect($sent)->toBe(1);
});

<?php

use App\Enums\PushType;
use App\Jobs\NotifyMemoryCreated;
use App\Jobs\SendPushNotification;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('queues the push for the other member, with the title the memory has by then', function () {
    Queue::fake([SendPushNotification::class]);
    $creator = User::factory()->create(['name' => 'Iqbal']);
    $partner = User::factory()->create();
    $space = sharedSpaceBetween($creator, $partner);
    $memory = Memory::factory()->for($space)->create(['title' => 'Liburan ke Bli', 'created_by' => $creator->id]);
    $memory->update(['title' => 'Liburan ke Bali']);

    NotifyMemoryCreated::dispatchSync($memory->id, $creator->id);

    Queue::assertPushed(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
        && $job->type === PushType::MemoryCreated
        && $job->title === 'Liburan ke Bali'
        && $job->body === 'Iqbal menyimpan kenangan baru'
        && $job->spaceId === $space->id
        && $job->memoryId === $memory->id);
});

it('sends nothing when the memory was deleted before the push went out', function () {
    Queue::fake([SendPushNotification::class]);
    $creator = User::factory()->create();
    $space = sharedSpaceBetween($creator, User::factory()->create());
    $memory = Memory::factory()->for($space)->create(['created_by' => $creator->id]);
    $memory->delete();

    NotifyMemoryCreated::dispatchSync($memory->id, $creator->id);

    Queue::assertNothingPushed();
});

it('sends nothing when the shared space was closed before the push went out', function () {
    Queue::fake([SendPushNotification::class]);
    $creator = User::factory()->create();
    $space = sharedSpaceBetween($creator, User::factory()->create());
    $memory = Memory::factory()->for($space)->create(['created_by' => $creator->id]);
    $space->delete();

    NotifyMemoryCreated::dispatchSync($memory->id, $creator->id);

    Queue::assertNothingPushed();
});

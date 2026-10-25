<?php

use App\Jobs\SendSyncPush;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Mockery\MockInterface;

/**
 * Get the sync pushes queued so far, as [gathered, due time].
 *
 * @return list<array{0: bool, 1: ?string}>
 */
function syncPushSchedule(): array
{
    return Queue::pushed(SendSyncPush::class)
        ->map(fn (SendSyncPush $job): array => [$job->gathered, $job->delay?->format('H:i:s')])
        ->values()
        ->all();
}

it('sends a data-only push that collapses per space to every device of the member', function () {
    $sent = new stdClass;
    $this->mock(Messaging::class, function (MockInterface $messaging) use ($sent): void {
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(function (CloudMessage $message, array $tokens) use ($sent): bool {
                $sent->message = json_decode(json_encode($message), true);
                $sent->tokens = $tokens;

                return true;
            })
            ->andReturn(MulticastSendReport::withItems([]));
    });
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-hp']);
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-tablet']);
    DeviceToken::factory()->create(['token' => 'fcm-orang-lain']);

    SendSyncPush::dispatchSync($user->id, 'id-ruang');

    expect($sent->tokens)->toEqualCanonicalizing(['fcm-hp', 'fcm-tablet'])
        ->and($sent->message)->toEqual([
            'data' => ['type' => 'sync', 'space_id' => 'id-ruang'],
            'android' => ['priority' => 'normal', 'collapse_key' => 'sync-id-ruang'],
        ]);
});

it('sends the first change at once and gathers the rest of the window into one push at its end', function () {
    Queue::fake([SendSyncPush::class]);
    $this->travelTo('2026-10-25 10:00:00');

    SendSyncPush::queueFor('id-pasangan', 'id-ruang');
    $this->travel(3)->seconds();
    SendSyncPush::queueFor('id-pasangan', 'id-ruang');
    $this->travel(3)->seconds();
    SendSyncPush::queueFor('id-pasangan', 'id-ruang');

    expect(syncPushSchedule())->toBe([[false, null], [true, '10:00:10']]);
});

it('sends at once again after a quiet window', function () {
    Queue::fake([SendSyncPush::class]);
    $this->travelTo('2026-10-25 10:00:00');

    SendSyncPush::queueFor('id-pasangan', 'id-ruang');
    $this->travel(11)->seconds();
    SendSyncPush::queueFor('id-pasangan', 'id-ruang');

    expect(syncPushSchedule())->toBe([[false, null], [false, null]]);
});

it('keeps a window for each member', function () {
    Queue::fake([SendSyncPush::class]);
    $this->travelTo('2026-10-25 10:00:00');

    SendSyncPush::queueFor('id-pasangan', 'id-ruang');
    SendSyncPush::queueFor('id-pemilik', 'id-ruang');

    expect(syncPushSchedule())->toBe([[false, null], [false, null]]);
});

it('starts the next window when the gathered push goes out', function () {
    Queue::fake([SendSyncPush::class]);
    $this->travelTo('2026-10-25 10:00:00');
    SendSyncPush::queueFor('id-pasangan', 'id-ruang');
    $this->travel(3)->seconds();
    SendSyncPush::queueFor('id-pasangan', 'id-ruang');
    $this->travelTo('2026-10-25 10:00:10');
    (new SendSyncPush('id-pasangan', 'id-ruang', gathered: true))->handle();
    $this->travel(2)->seconds();

    SendSyncPush::queueFor('id-pasangan', 'id-ruang');

    expect(syncPushSchedule())->toBe([[false, null], [true, '10:00:10'], [true, '10:00:20']]);
});

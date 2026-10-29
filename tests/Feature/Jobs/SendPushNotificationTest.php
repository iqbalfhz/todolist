<?php

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery\MockInterface;

/**
 * Replace FCM with a double that records the one multicast it is asked to send.
 *
 * @param  list<string>  $unregisteredTokens
 * @param  array<string, MessagingException>  $failures  Other failures, by token.
 */
function fakeFcm(object $sent, array $unregisteredTokens = [], array $failures = []): void
{
    test()->mock(Messaging::class, function (MockInterface $messaging) use ($sent, $unregisteredTokens, $failures): void {
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(function (CloudMessage $message, array $tokens) use ($sent): bool {
                $sent->message = json_decode(json_encode($message), true);
                $sent->tokens = $tokens;

                return true;
            })
            ->andReturn(MulticastSendReport::withItems([
                ...array_map(
                    fn (string $token) => SendReport::failure(MessageTarget::with(MessageTarget::TOKEN, $token), new NotFound('Unregistered')),
                    $unregisteredTokens,
                ),
                ...array_map(
                    fn (string $token, MessagingException $error) => SendReport::failure(MessageTarget::with(MessageTarget::TOKEN, $token), $error),
                    array_keys($failures),
                    $failures,
                ),
            ]));
    });
}

it('sends the push to every device of the recipient', function () {
    $sent = new stdClass;
    fakeFcm($sent);
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-hp']);
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-tablet']);
    DeviceToken::factory()->create(['token' => 'fcm-orang-lain']);

    SendPushNotification::dispatchSync($user->id, PushType::TaskReminder, 'Beli galon', 'Tenggat hari ini, 17.00', 'id-ruang', taskId: 'id-tugas');

    expect($sent->tokens)->toEqualCanonicalizing(['fcm-hp', 'fcm-tablet'])
        ->and($sent->message['notification'])->toBe(['title' => 'Beli galon', 'body' => 'Tenggat hari ini, 17.00'])
        ->and($sent->message['data'])->toBe(['type' => 'task_reminder', 'space_id' => 'id-ruang', 'task_id' => 'id-tugas']);
});

it('uses the channel, priority, tag, and visibility of each push type', function (PushType $type, array $ids, array $android) {
    $sent = new stdClass;
    fakeFcm($sent);
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create();

    SendPushNotification::dispatchSync($user->id, $type, 'Judul', 'Isi', 'id-ruang', ...$ids);

    expect($sent->message['android'])->toBe($android);
})->with([
    'a task reminder' => [
        PushType::TaskReminder,
        ['taskId' => 'id-tugas'],
        ['priority' => 'high', 'notification' => ['channel_id' => 'pengingat', 'tag' => 'task-id-tugas']],
    ],
    'a task assignment' => [
        PushType::TaskAssigned,
        ['taskId' => 'id-tugas'],
        ['priority' => 'high', 'notification' => ['channel_id' => 'aktivitas', 'tag' => 'task-id-tugas']],
    ],
    'a completed task' => [
        PushType::TaskCompleted,
        ['taskId' => 'id-tugas'],
        ['priority' => 'normal', 'notification' => ['channel_id' => 'aktivitas', 'tag' => 'task-id-tugas']],
    ],
    'a deposit, hidden on the lock screen' => [
        PushType::SavingsDeposit,
        ['savingsGoalId' => 'id-target'],
        ['priority' => 'normal', 'notification' => ['channel_id' => 'aktivitas', 'tag' => 'goal-id-target', 'visibility' => 'PRIVATE']],
    ],
    'a withdrawal, hidden on the lock screen' => [
        PushType::SavingsWithdrawal,
        ['savingsGoalId' => 'id-target'],
        ['priority' => 'normal', 'notification' => ['channel_id' => 'aktivitas', 'tag' => 'goal-id-target', 'visibility' => 'PRIVATE']],
    ],
    'an achieved goal, hidden on the lock screen' => [
        PushType::SavingsAchieved,
        ['savingsGoalId' => 'id-target'],
        ['priority' => 'normal', 'notification' => ['channel_id' => 'aktivitas', 'tag' => 'goal-id-target', 'visibility' => 'PRIVATE']],
    ],
    'a partner who joined' => [
        PushType::PartnerJoined,
        [],
        ['priority' => 'normal', 'notification' => ['channel_id' => 'aktivitas', 'tag' => 'space-id-ruang']],
    ],
    'a special date reminder' => [
        PushType::SpecialDateReminder,
        ['specialDateId' => 'id-tanggal'],
        ['priority' => 'high', 'notification' => ['channel_id' => 'pengingat', 'tag' => 'date-id-tanggal']],
    ],
    'a member birthday reminder' => [
        PushType::SpecialDateReminder,
        ['memberId' => 'id-anggota'],
        ['priority' => 'high', 'notification' => ['channel_id' => 'pengingat', 'tag' => 'birthday-id-anggota']],
    ],
    'a trip reminder' => [
        PushType::TripReminder,
        ['tripId' => 'id-perjalanan'],
        ['priority' => 'high', 'notification' => ['channel_id' => 'pengingat', 'tag' => 'trip-id-perjalanan']],
    ],
    'a deposit reminder, hidden on the lock screen' => [
        PushType::SavingsReminder,
        ['savingsGoalId' => 'id-target'],
        ['priority' => 'high', 'notification' => ['channel_id' => 'pengingat', 'tag' => 'goal-id-target', 'visibility' => 'PRIVATE']],
    ],
    'a new memory' => [
        PushType::MemoryCreated,
        ['memoryId' => 'id-kenangan'],
        ['priority' => 'normal', 'notification' => ['channel_id' => 'aktivitas', 'tag' => 'memory-id-kenangan']],
    ],
]);

it('sends the special date or the member a reminder is about in the data', function (array $ids, array $data) {
    $sent = new stdClass;
    fakeFcm($sent);
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create();

    SendPushNotification::dispatchSync($user->id, PushType::SpecialDateReminder, 'Tanggal penting', 'Hari jadi kita besok', 'id-ruang', ...$ids);

    expect($sent->message['data'])->toBe($data);
})->with([
    'a special date' => [['specialDateId' => 'id-tanggal'], ['type' => 'special_date_reminder', 'space_id' => 'id-ruang', 'special_date_id' => 'id-tanggal']],
    'a member birthday' => [['memberId' => 'id-anggota'], ['type' => 'special_date_reminder', 'space_id' => 'id-ruang', 'member_id' => 'id-anggota']],
]);

it('sends the trip a reminder is about in the data', function () {
    $sent = new stdClass;
    fakeFcm($sent);
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create();

    SendPushNotification::dispatchSync($user->id, PushType::TripReminder, 'Perjalanan', 'Liburan ke Bali besok', 'id-ruang', tripId: 'id-perjalanan');

    expect($sent->message['data'])->toBe(['type' => 'trip_reminder', 'space_id' => 'id-ruang', 'trip_id' => 'id-perjalanan']);
});

it('sends the memory a push is about in the data', function () {
    $sent = new stdClass;
    fakeFcm($sent);
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create();

    SendPushNotification::dispatchSync($user->id, PushType::MemoryCreated, 'Liburan ke Bali', 'Chika menyimpan kenangan baru', 'id-ruang', memoryId: 'id-kenangan');

    expect($sent->message['data'])->toBe(['type' => 'memory_created', 'space_id' => 'id-ruang', 'memory_id' => 'id-kenangan']);
});

it('sends nothing when the recipient turned that kind of push off', function (PushType $type, string $preference) {
    $this->mock(Messaging::class)->shouldNotReceive('sendMulticast');
    $user = User::factory()->create(['notification_prefs' => [$preference => false]]);
    DeviceToken::factory()->for($user)->create();

    SendPushNotification::dispatchSync($user->id, $type, 'Judul', 'Isi', 'id-ruang');
})->with([
    'task reminders' => [PushType::TaskReminder, 'task_reminders'],
    'task assignments' => [PushType::TaskAssigned, 'partner_activity'],
    'completed tasks' => [PushType::TaskCompleted, 'partner_activity'],
    'deposits' => [PushType::SavingsDeposit, 'savings_activity'],
    'achieved goals' => [PushType::SavingsAchieved, 'savings_activity'],
    'withdrawals' => [PushType::SavingsWithdrawal, 'savings_activity'],
    'special dates' => [PushType::SpecialDateReminder, 'special_dates'],
    'trip reminders' => [PushType::TripReminder, 'trip_reminders'],
    'deposit reminders' => [PushType::SavingsReminder, 'savings_reminders'],
    'new memories' => [PushType::MemoryCreated, 'partner_activity'],
]);

it('sends partner_joined even when every preference is off', function () {
    $sent = new stdClass;
    fakeFcm($sent);
    $user = User::factory()->create(['notification_prefs' => [
        'task_reminders' => false,
        'partner_activity' => false,
        'savings_activity' => false,
    ]]);
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-hp']);

    SendPushNotification::dispatchSync($user->id, PushType::PartnerJoined, 'Ruang Bersama', 'Isi', 'id-ruang');

    expect($sent->tokens)->toBe(['fcm-hp']);
});

it('sends nothing when the recipient has no device', function () {
    $this->mock(Messaging::class)->shouldNotReceive('sendMulticast');
    $user = User::factory()->create();

    SendPushNotification::dispatchSync($user->id, PushType::TaskReminder, 'Judul', 'Isi', 'id-ruang');
});

// Setting up FCM throws in tests, as it does on a machine without Firebase credentials.
it('does not set up FCM when the recipient has no device', function () {
    $user = User::factory()->create();

    SendPushNotification::dispatchSync($user->id, PushType::TaskReminder, 'Judul', 'Isi', 'id-ruang');
})->throwsNoExceptions();

it('removes the tokens FCM reports as unregistered', function () {
    fakeFcm(new stdClass, unregisteredTokens: ['fcm-sudah-dicopot']);
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-sudah-dicopot']);
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-masih-aktif']);

    SendPushNotification::dispatchSync($user->id, PushType::TaskReminder, 'Judul', 'Isi', 'id-ruang');

    $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm-sudah-dicopot']);
    $this->assertDatabaseHas('device_tokens', ['token' => 'fcm-masih-aktif']);
});

it('removes the tokens FCM rejects as malformed', function () {
    fakeFcm(new stdClass, failures: ['fcm-rusak' => new InvalidMessage('The registration token is not a valid FCM registration token')]);
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-rusak']);

    SendPushNotification::dispatchSync($user->id, PushType::TaskReminder, 'Judul', 'Isi', 'id-ruang');

    $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm-rusak']);
});

it('logs a failure that is not about a dead token, without the token, and keeps the device', function () {
    Log::spy();
    fakeFcm(new stdClass, failures: ['fcm-hp' => new ServerUnavailable('FCM sedang tidak tersedia')]);
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-hp']);

    SendPushNotification::dispatchSync($user->id, PushType::TaskReminder, 'Judul', 'Isi', 'id-ruang');

    Log::shouldHaveReceived('warning')->once()->with('FCM did not deliver a push.', [
        'type' => 'task_reminder',
        'user_id' => $user->id,
        'failed_devices' => 1,
        'errors' => ['FCM sedang tidak tersedia'],
    ]);
    $this->assertDatabaseHas('device_tokens', ['token' => 'fcm-hp']);
});

it('logs nothing when every failure is about a dead token', function () {
    Log::spy();
    fakeFcm(
        new stdClass,
        unregisteredTokens: ['fcm-sudah-dicopot'],
        failures: ['fcm-rusak' => new InvalidMessage('The registration token is not a valid FCM registration token')],
    );
    $user = User::factory()->create();
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-sudah-dicopot']);
    DeviceToken::factory()->for($user)->create(['token' => 'fcm-rusak']);

    SendPushNotification::dispatchSync($user->id, PushType::TaskReminder, 'Judul', 'Isi', 'id-ruang');

    Log::shouldNotHaveReceived('warning');
});

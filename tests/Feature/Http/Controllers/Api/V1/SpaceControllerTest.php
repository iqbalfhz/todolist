<?php

use App\Enums\PushType;
use App\Jobs\SendPushNotification;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('lists the personal space with every field of the contract', function () {
    $this->travelTo('2026-10-08 10:20:00.123456');
    $user = actingAsApiUser();
    $space = $user->personalSpace();

    $response = $this->getJson('/api/v1/spaces');

    $response->assertOk()->assertExactJson([
        'data' => [
            [
                'id' => $space->id,
                'name' => 'Ruang Pribadi',
                'type' => 'personal',
                'timezone' => 'Asia/Jakarta',
                'owner_id' => $user->id,
                'members' => [
                    [
                        'id' => $user->id,
                        'name' => $user->name,
                        'color' => 'mawar',
                        'avatar_url' => null,
                        'avatar_version' => 0,
                        'birth_date' => null,
                        'role' => 'owner',
                        'joined_at' => '2026-10-08T10:20:00.123456Z',
                    ],
                ],
                'created_at' => '2026-10-08T10:20:00.123456Z',
                'updated_at' => '2026-10-08T10:20:00.123456Z',
                'deleted_at' => null,
            ],
        ],
    ]);
});

it('lists the shared space with both members, the owner first', function () {
    $user = actingAsApiUser();
    $partner = User::factory()->create(['name' => 'Pasangan', 'color' => 'ungu', 'birth_date' => '--03-02']);
    $sharedSpace = sharedSpaceBetween($partner, $user);

    $response = $this->getJson('/api/v1/spaces');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.1.id', $sharedSpace->id)
        ->assertJsonPath('data.1.type', 'shared')
        ->assertJsonPath('data.1.members.0.id', $partner->id)
        ->assertJsonPath('data.1.members.0.role', 'owner')
        ->assertJsonPath('data.1.members.0.color', 'ungu')
        ->assertJsonPath('data.1.members.0.birth_date', '--03-02')
        ->assertJsonPath('data.1.members.1.id', $user->id)
        ->assertJsonPath('data.1.members.1.role', 'member');
});

it('leaves out spaces the user is not a member of', function () {
    $user = actingAsApiUser();
    $partner = User::factory()->withPersonalSpace()->create();
    sharedSpaceBetween($user, $partner);
    sharedSpaceBetween(User::factory()->create(), User::factory()->create());

    $response = $this->getJson('/api/v1/spaces');

    $response->assertOk()->assertJsonCount(2, 'data');
    expect($response->json('data.*.id'))->not->toContain($partner->personalSpace()->id);
});

it('returns 401 when no token is sent', function () {
    $response = $this->getJson('/api/v1/spaces');

    $response->assertUnauthorized();
});

it('closes the shared space for both partners and returns 204', function () {
    Queue::fake([SendPushNotification::class]);
    $this->travelTo('2026-10-30 09:15:00.250000');
    [$user, $partner, $sharedSpace] = actingAsPartnered();

    $response = $this->deleteJson("/api/v1/spaces/{$sharedSpace->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted($sharedSpace);
    expect($sharedSpace->fresh()->deleted_at->format('Y-m-d H:i:s.u'))->toBe('2026-10-30 09:15:00.250000');
    $this->assertDatabaseHas('space_members', ['space_id' => $sharedSpace->id, 'user_id' => $user->id]);
    $this->assertDatabaseHas('space_members', ['space_id' => $sharedSpace->id, 'user_id' => $partner->id]);
});

it('queues a partner_left push for the partner only', function () {
    Queue::fake([SendPushNotification::class]);
    [$user, $partner, $sharedSpace] = actingAsPartnered();
    $user->update(['name' => 'Iqbal']);

    $this->deleteJson("/api/v1/spaces/{$sharedSpace->id}");

    Queue::assertPushedTimes(SendPushNotification::class, 1);
    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $partner->id
        && $job->type === PushType::PartnerLeft
        && $job->title === 'Ruang Bersama'
        && $job->body === 'Iqbal keluar dari Ruang Bersama. Isinya disimpan 30 hari kalau kalian ingin bergabung lagi.'
        && $job->spaceId === $sharedSpace->id);
});

it('returns 204 again without another push when the space is already closed', function (Closure $closer) {
    Queue::fake([SendPushNotification::class]);
    [$user, $partner, $sharedSpace] = actingAsPartnered();
    $this->actingAs($closer($user, $partner), 'sanctum')->deleteJson("/api/v1/spaces/{$sharedSpace->id}");

    $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/spaces/{$sharedSpace->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted($sharedSpace);
    Queue::assertPushedTimes(SendPushNotification::class, 1);
})->with([
    'closed by the same member before' => [fn (User $user, User $partner) => $user],
    'closed by the partner first' => [fn (User $user, User $partner) => $partner],
]);

it('takes the closed space out of the spaces of both partners', function () {
    Queue::fake([SendPushNotification::class]);
    [$user, $partner, $sharedSpace] = actingAsPartnered();

    $this->deleteJson("/api/v1/spaces/{$sharedSpace->id}");

    $this->getJson('/api/v1/spaces')->assertOk()->assertJsonPath('data.*.id', [$user->personalSpace()->id]);
    $this->actingAs($partner, 'sanctum')->getJson('/api/v1/spaces')
        ->assertOk()
        ->assertJsonPath('data.*.id', [$partner->personalSpace()->id]);
});

it('returns 404 for every write to what the closed space held, from either partner', function (Closure $writer) {
    Queue::fake([SendPushNotification::class]);
    [$user, $partner, $sharedSpace] = actingAsPartnered();
    $task = Task::factory()->for(TaskList::factory()->for($sharedSpace))->create();
    $this->deleteJson("/api/v1/spaces/{$sharedSpace->id}");

    $response = $this->actingAs($writer($user, $partner), 'sanctum')
        ->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Judul baru']);

    $response->assertNotFound()->assertJsonPath('code', 'not_found');
    expect($task->fresh()->title)->not->toBe('Judul baru');
})->with([
    'the member who closed it' => [fn (User $user, User $partner) => $user],
    'the partner' => [fn (User $user, User $partner) => $partner],
]);

it('lets both partners make a new invitation code once the space is closed', function () {
    Queue::fake([SendPushNotification::class]);
    [, $partner, $sharedSpace] = actingAsPartnered();
    $this->deleteJson("/api/v1/spaces/{$sharedSpace->id}");

    $this->postJson('/api/v1/invitations')->assertCreated();
    $this->actingAs($partner, 'sanctum')->postJson('/api/v1/invitations')->assertCreated();
});

it('returns 422 with the validation_failed code for the personal space', function () {
    Queue::fake([SendPushNotification::class]);
    $user = actingAsApiUser();
    $personalSpace = $user->personalSpace();

    $response = $this->deleteJson("/api/v1/spaces/{$personalSpace->id}");

    $response->assertUnprocessable()->assertExactJson([
        'message' => 'Ruang Pribadi tidak bisa dihapus.',
        'code' => 'validation_failed',
        'errors' => ['id' => ['Ruang Pribadi tidak bisa dihapus.']],
    ]);
    $this->assertNotSoftDeleted($personalSpace);
    Queue::assertNothingPushed();
});

it('returns 404 with the not_found code for a space the user is not a member of', function (Closure $otherSpace) {
    Queue::fake([SendPushNotification::class]);
    [, $partner] = actingAsPartnered();
    $space = $otherSpace($partner);

    $response = $this->deleteJson("/api/v1/spaces/{$space->id}");

    $response->assertNotFound()->assertExactJson([
        'message' => 'Data tidak ditemukan.',
        'code' => 'not_found',
    ]);
    $this->assertNotSoftDeleted($space);
    Queue::assertNothingPushed();
})->with([
    'the shared space of another couple' => [fn (User $partner) => sharedSpaceBetween(User::factory()->create(), User::factory()->create())],
    'the personal space of the partner' => [fn (User $partner) => $partner->personalSpace()],
]);

it('returns 404 with the not_found code for an ID no space has', function () {
    actingAsApiUser();

    $response = $this->deleteJson('/api/v1/spaces/'.newId());

    $response->assertNotFound()->assertJsonPath('code', 'not_found');
});

it('returns 422 with the validation_failed code for an uppercase ID', function () {
    [, , $sharedSpace] = actingAsPartnered();

    $response = $this->deleteJson('/api/v1/spaces/'.strtoupper($sharedSpace->id));

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.id.0', 'ID harus berupa ULID huruf kecil.');
    $this->assertNotSoftDeleted($sharedSpace);
});

it('returns 401 when no token is sent to close a space', function () {
    $sharedSpace = sharedSpaceBetween(User::factory()->create(), User::factory()->create());

    $response = $this->deleteJson("/api/v1/spaces/{$sharedSpace->id}");

    $response->assertUnauthorized();
    $this->assertNotSoftDeleted($sharedSpace);
});

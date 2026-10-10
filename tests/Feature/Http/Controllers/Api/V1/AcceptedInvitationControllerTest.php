<?php

use App\Enums\PushType;
use App\Enums\SpaceType;
use App\Jobs\SendPushNotification;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

/**
 * Create a registered user who is waiting for a partner with the given invitation code.
 *
 * @param  array<string, mixed>  $attributes
 */
function inviterWithCode(string $code, array $attributes = []): User
{
    $inviter = User::factory()->withPersonalSpace()->create($attributes);

    SpaceInvitation::factory()->for($inviter, 'creator')->create(['code' => $code]);

    return $inviter;
}

it('creates the shared space for both partners and returns 201', function () {
    Queue::fake([SendPushNotification::class]);
    $inviter = inviterWithCode('K7M2QX', ['timezone' => 'Asia/Makassar', 'color' => 'ungu']);
    $joiner = actingAsApiUser();

    $response = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Ruang Bersama')
        ->assertJsonPath('data.type', 'shared')
        ->assertJsonPath('data.timezone', 'Asia/Makassar')
        ->assertJsonPath('data.owner_id', $inviter->id)
        ->assertJsonPath('data.members.0.id', $inviter->id)
        ->assertJsonPath('data.members.0.role', 'owner')
        ->assertJsonPath('data.members.1.id', $joiner->id)
        ->assertJsonPath('data.members.1.role', 'member');
    $spaceId = $response->json('data.id');
    $this->assertDatabaseHas('task_lists', ['space_id' => $spaceId, 'name' => 'Tugas', 'created_by' => $inviter->id]);
    $this->assertDatabaseHas('space_invitations', ['code' => 'K7M2QX', 'accepted_by' => $joiner->id, 'space_id' => $spaceId]);
});

it('accepts the code however it was typed', function (string $typed) {
    Queue::fake([SendPushNotification::class]);
    inviterWithCode('K7M2QX');
    actingAsApiUser();

    $response = $this->postJson('/api/v1/invitations/accept', ['code' => $typed]);

    $response->assertCreated();
})->with([
    'in lowercase' => ['k7m2qx'],
    'with a hyphen' => ['K7M-2QX'],
    'with spaces' => ['k7m 2qx'],
]);

it('returns 200 with the same space when the code is sent again', function () {
    Queue::fake([SendPushNotification::class]);
    inviterWithCode('K7M2QX');
    actingAsApiUser();
    $spaceId = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX'])->json('data.id');

    $response = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX']);

    $response->assertOk()->assertJsonPath('data.id', $spaceId);
    expect(Space::query()->where('type', SpaceType::Shared)->count())->toBe(1);
});

it('returns 200 with the same space when partners enter each other\'s code', function () {
    Queue::fake([SendPushNotification::class]);
    $partner = inviterWithCode('K7M2QX');
    $user = actingAsApiUser();
    SpaceInvitation::factory()->for($user, 'creator')->create(['code' => 'ABCDEF']);
    $spaceId = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX'])->json('data.id');

    $response = $this->actingAs($partner, 'sanctum')->postJson('/api/v1/invitations/accept', ['code' => 'ABCDEF']);

    $response->assertOk()->assertJsonPath('data.id', $spaceId);
    expect(Space::query()->where('type', SpaceType::Shared)->count())->toBe(1);
});

it('returns 409 with the shared_space_exists code when the joiner already has another partner', function () {
    inviterWithCode('K7M2QX');
    $joiner = actingAsApiUser();
    sharedSpaceBetween($joiner, User::factory()->create());

    $response = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX']);

    $response->assertConflict()->assertJsonPath('code', 'shared_space_exists');
    $this->assertDatabaseHas('space_invitations', ['code' => 'K7M2QX', 'accepted_at' => null]);
});

it('returns the same 422 for every code that cannot be used', function (Closure $arrange) {
    $joiner = actingAsApiUser();
    $arrange($joiner);

    $response = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX']);

    $response->assertUnprocessable()->assertExactJson([
        'message' => 'Kode tidak berlaku, minta kode baru.',
        'code' => 'invitation_invalid',
    ]);
    expect($joiner->sharedSpace())->toBeNull();
})->with([
    'a code nobody created' => [fn () => null],
    'an expired code' => [fn () => SpaceInvitation::factory()->expired()->create(['code' => 'K7M2QX'])],
    'a revoked code' => [fn () => SpaceInvitation::factory()->revoked()->create(['code' => 'K7M2QX'])],
    'a code that was already used' => [fn () => SpaceInvitation::factory()->accepted()->create(['code' => 'K7M2QX'])],
    'their own code' => [fn (User $joiner) => SpaceInvitation::factory()->for($joiner, 'creator')->create(['code' => 'K7M2QX'])],
    'a code from someone who is already paired' => [function () {
        $inviter = inviterWithCode('K7M2QX');
        sharedSpaceBetween($inviter, User::factory()->create());
    }],
]);

it('revokes the other open codes of both partners', function () {
    Queue::fake([SendPushNotification::class]);
    $inviter = inviterWithCode('K7M2QX');
    $joiner = actingAsApiUser();
    $joinerCode = SpaceInvitation::factory()->for($joiner, 'creator')->create();
    $strangerCode = SpaceInvitation::factory()->create();

    $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX']);

    expect($joinerCode->fresh()->revoked_at)->not->toBeNull()
        ->and($strangerCode->fresh()->revoked_at)->toBeNull();
});

it('gives the joiner the first free color when both partners have the same one', function (string $shared, string $expected) {
    Queue::fake([SendPushNotification::class]);
    inviterWithCode('K7M2QX', ['color' => $shared]);
    $joiner = actingAsApiUser();
    $joiner->update(['color' => $shared]);

    $response = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX']);

    $response->assertCreated()
        ->assertJsonPath('data.members.0.color', $shared)
        ->assertJsonPath('data.members.1.color', $expected);
})->with([
    'both mawar' => ['mawar', 'ungu'],
    'both ungu' => ['ungu', 'mawar'],
]);

it('keeps the joiner\'s color when the partners already differ', function () {
    Queue::fake([SendPushNotification::class]);
    inviterWithCode('K7M2QX', ['color' => 'toska']);
    $joiner = actingAsApiUser();
    $joiner->update(['color' => 'biru']);

    $response = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX']);

    $response->assertCreated()->assertJsonPath('data.members.1.color', 'biru');
});

it('queues a partner_joined push for the inviter', function () {
    Queue::fake([SendPushNotification::class]);
    $inviter = inviterWithCode('K7M2QX');
    $joiner = actingAsApiUser();
    $joiner->update(['name' => 'Pasangan']);

    $spaceId = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX'])->json('data.id');

    Queue::assertPushed(SendPushNotification::class, fn (SendPushNotification $job) => $job->userId === $inviter->id
        && $job->type === PushType::PartnerJoined
        && $job->title === 'Ruang Bersama'
        && $job->body === 'Pasangan sudah bergabung. Ruang Bersama kalian siap.'
        && $job->spaceId === $spaceId);
});

it('returns 422 with the validation_failed code when no code is sent', function () {
    actingAsApiUser();

    $response = $this->postJson('/api/v1/invitations/accept', []);

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.code.0', 'Kode wajib diisi.');
});

it('returns 429 after ten attempts by the same account within a minute', function () {
    actingAsApiUser();
    foreach (range(1, 10) as $attempt) {
        $this->postJson('/api/v1/invitations/accept', ['code' => 'SALAH2']);
    }

    $response = $this->postJson('/api/v1/invitations/accept', ['code' => 'SALAH2']);

    $response->assertTooManyRequests()->assertJsonPath('code', 'too_many_requests');
});

it('returns 401 when no token is sent', function () {
    $response = $this->postJson('/api/v1/invitations/accept', ['code' => 'K7M2QX']);

    $response->assertUnauthorized();
});

<?php

use App\Models\User;

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
    $partner = User::factory()->create(['name' => 'Pasangan', 'color' => 'ungu']);
    $sharedSpace = sharedSpaceBetween($partner, $user);

    $response = $this->getJson('/api/v1/spaces');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.1.id', $sharedSpace->id)
        ->assertJsonPath('data.1.type', 'shared')
        ->assertJsonPath('data.1.members.0.id', $partner->id)
        ->assertJsonPath('data.1.members.0.role', 'owner')
        ->assertJsonPath('data.1.members.0.color', 'ungu')
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

<?php

use App\Models\SpaceInvitation;
use App\Models\User;

it('creates a six-character code that is valid for 48 hours and returns 201', function () {
    $this->travelTo('2026-10-08 10:16:00');
    $user = actingAsApiUser();

    $response = $this->postJson('/api/v1/invitations');

    $response->assertCreated()->assertJsonPath('data.expires_at', '2026-10-10T10:16:00.000000Z');
    expect($response->json('data.code'))->toMatch('/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{6}$/');
    $this->assertDatabaseHas('space_invitations', ['created_by' => $user->id, 'code' => $response->json('data.code')]);
});

it('returns 200 with the same code while it is still active', function () {
    $user = actingAsApiUser();
    SpaceInvitation::factory()->for($user, 'creator')->create(['code' => 'K7M2QX']);

    $response = $this->postJson('/api/v1/invitations');

    $response->assertOk()->assertJsonPath('data.code', 'K7M2QX');
    $this->assertDatabaseCount('space_invitations', 1);
});

it('creates a new code when the previous one can no longer be used', function (string $state) {
    $user = actingAsApiUser();
    SpaceInvitation::factory()->for($user, 'creator')->{$state}()->create(['code' => 'K7M2QX']);

    $response = $this->postJson('/api/v1/invitations');

    $response->assertCreated();
    expect($response->json('data.code'))->not->toBe('K7M2QX');
})->with(['expired', 'revoked']);

it('revokes the active code and returns a new one when renew is sent', function () {
    $user = actingAsApiUser();
    $previous = SpaceInvitation::factory()->for($user, 'creator')->create(['code' => 'K7M2QX']);

    $response = $this->postJson('/api/v1/invitations', ['renew' => true]);

    $response->assertCreated();
    expect($response->json('data.code'))->not->toBe('K7M2QX')
        ->and($previous->fresh()->revoked_at)->not->toBeNull();
});

it('returns 409 with the shared_space_exists code when the user already has a partner', function () {
    $user = actingAsApiUser();
    sharedSpaceBetween($user, User::factory()->create());

    $response = $this->postJson('/api/v1/invitations');

    $response->assertConflict()->assertExactJson([
        'message' => 'Kamu sudah punya Ruang Bersama.',
        'code' => 'shared_space_exists',
    ]);
    $this->assertDatabaseEmpty('space_invitations');
});

it('returns 422 when renew is not a boolean', function () {
    actingAsApiUser();

    $response = $this->postJson('/api/v1/invitations', ['renew' => 'nanti']);

    $response->assertUnprocessable()->assertJsonValidationErrors(['renew']);
});

it('returns 401 when no token is sent', function () {
    $response = $this->postJson('/api/v1/invitations');

    $response->assertUnauthorized();
});

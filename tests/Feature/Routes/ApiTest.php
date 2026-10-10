<?php

use App\Models\User;

it('returns the user who owns the bearer token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('ponsel')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/me');

    $response->assertOk()->assertJsonPath('data.id', $user->id);
});

it('returns 401 when no token is provided', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
});

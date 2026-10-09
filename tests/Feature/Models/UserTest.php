<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('uses a lowercase ULID as the primary key', function () {
    $user = User::factory()->create();

    expect($user->id)->toMatch('/^[0-7][0-9a-hjkmnp-tv-z]{25}$/');
});

it('serializes timestamps as ISO 8601 with microseconds after a database round trip', function () {
    $this->travelTo('2026-01-02 03:04:05.678901');

    $user = User::factory()->create();

    expect($user->fresh()->toArray())->toMatchArray([
        'created_at' => '2026-01-02T03:04:05.678901Z',
        'updated_at' => '2026-01-02T03:04:05.678901Z',
    ]);
});

it('can be issued an API token that resolves back to the user', function () {
    $user = User::factory()->create();

    $token = $user->createToken('ponsel');

    expect(PersonalAccessToken::findToken($token->plainTextToken)->tokenable_id)->toBe($user->id);
});

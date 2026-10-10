<?php

use App\Models\DeviceToken;
use App\Models\User;

describe('store', function () {
    it('registers a new FCM token and returns 201', function () {
        $user = actingAsApiUser();

        $response = $this->postJson('/api/v1/devices', ['token' => 'fcm-token-baru', 'platform' => 'android']);

        $response->assertCreated()->assertExactJson([
            'data' => ['token' => 'fcm-token-baru', 'platform' => 'android'],
        ]);
        $this->assertDatabaseHas('device_tokens', ['token' => 'fcm-token-baru', 'user_id' => $user->id, 'platform' => 'android']);
    });

    it('returns 200 without a second row when the token is sent again', function () {
        $user = actingAsApiUser();
        DeviceToken::factory()->for($user)->create(['token' => 'fcm-token-lama']);

        $response = $this->postJson('/api/v1/devices', ['token' => 'fcm-token-lama', 'platform' => 'android']);

        $response->assertOk();
        $this->assertDatabaseCount('device_tokens', 1);
    });

    it('moves a token that another account registered to the signed-in user', function () {
        $previousOwner = User::factory()->create();
        DeviceToken::factory()->for($previousOwner)->create(['token' => 'fcm-hp-bekas']);
        $user = actingAsApiUser();

        $response = $this->postJson('/api/v1/devices', ['token' => 'fcm-hp-bekas', 'platform' => 'android']);

        $response->assertOk();
        $this->assertDatabaseHas('device_tokens', ['token' => 'fcm-hp-bekas', 'user_id' => $user->id]);
        $this->assertDatabaseCount('device_tokens', 1);
    });

    it('treats tokens that differ only in letter case as different devices', function () {
        $user = actingAsApiUser();
        DeviceToken::factory()->for($user)->create(['token' => 'fcm-TOKEN']);

        $response = $this->postJson('/api/v1/devices', ['token' => 'fcm-token', 'platform' => 'android']);

        $response->assertCreated();
        $this->assertDatabaseCount('device_tokens', 2);
    });

    it('returns 422 for invalid values', function (array $payload, string $field) {
        actingAsApiUser();

        $response = $this->postJson('/api/v1/devices', $payload);

        $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
        $this->assertDatabaseEmpty('device_tokens');
    })->with([
        'no token' => [['platform' => 'android'], 'token'],
        'a token longer than 512 characters' => [['token' => str_repeat('a', 513), 'platform' => 'android'], 'token'],
        'no platform' => [['token' => 'fcm-token'], 'platform'],
        'an unknown platform' => [['token' => 'fcm-token', 'platform' => 'windows'], 'platform'],
    ]);

    it('returns 401 when no token is sent', function () {
        $response = $this->postJson('/api/v1/devices', ['token' => 'fcm-token', 'platform' => 'android']);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('removes the FCM token of the signed-in user', function () {
        $user = actingAsApiUser();
        DeviceToken::factory()->for($user)->create(['token' => 'fcm:token-hp_ini']);

        $response = $this->deleteJson('/api/v1/devices/fcm:token-hp_ini');

        $response->assertNoContent();
        $this->assertDatabaseEmpty('device_tokens');
    });

    it('returns 204 and keeps a token that belongs to another account', function () {
        actingAsApiUser();
        DeviceToken::factory()->create(['token' => 'fcm-orang-lain']);

        $response = $this->deleteJson('/api/v1/devices/fcm-orang-lain');

        $response->assertNoContent();
        $this->assertDatabaseHas('device_tokens', ['token' => 'fcm-orang-lain']);
    });

    it('returns 204 when the token was already removed', function () {
        actingAsApiUser();

        $response = $this->deleteJson('/api/v1/devices/fcm-sudah-hilang');

        $response->assertNoContent();
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->deleteJson('/api/v1/devices/fcm-token');

        $response->assertUnauthorized();
    });
});

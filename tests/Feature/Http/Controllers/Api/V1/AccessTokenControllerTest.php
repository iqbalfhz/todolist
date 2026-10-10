<?php

use App\Enums\SpaceType;
use App\Models\DeviceToken;
use App\Models\User;

describe('store', function () {
    it('signs in and returns a token with the user', function () {
        $user = User::factory()->withPersonalSpace()->create(['email' => 'iqbal@contoh.test']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'iqbal@contoh.test',
            'password' => 'password',
            'device_name' => 'Redmi Note 13',
        ]);

        $response->assertOk()->assertJsonPath('data.user.id', $user->id);
        expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'Redmi Note 13']);
    });

    it('accepts the email in any letter case', function () {
        User::factory()->withPersonalSpace()->create(['email' => 'iqbal@contoh.test']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'Iqbal@Contoh.Test',
            'password' => 'password',
            'device_name' => 'Redmi Note 13',
        ]);

        $response->assertOk();
    });

    it('returns 422 with the invalid_credentials code for a wrong password or an unknown email', function (string $email, string $password) {
        User::factory()->create(['email' => 'iqbal@contoh.test']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
            'device_name' => 'Redmi Note 13',
        ]);

        $response->assertUnprocessable()->assertExactJson([
            'message' => 'Email atau kata sandi salah.',
            'code' => 'invalid_credentials',
        ]);
        $this->assertDatabaseEmpty('personal_access_tokens');
    })->with([
        'a wrong password' => ['iqbal@contoh.test', 'kata-sandi-salah'],
        'an email that is not registered' => ['orang-lain@contoh.test', 'password'],
    ]);

    it('returns 422 naming every required field that is missing', function () {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    });

    it('returns 429 after five attempts for the same email from the same IP within a minute', function () {
        $credentials = ['email' => 'iqbal@contoh.test', 'password' => 'kata-sandi-salah', 'device_name' => 'Redmi Note 13'];
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/login', $credentials);
        }

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertTooManyRequests()
            ->assertJsonPath('code', 'too_many_requests')
            ->assertHeader('Retry-After');
    });

    it('creates the personal space on first sign-in for an account made outside registration', function () {
        $user = User::factory()->create(['email' => 'admin@contoh.test']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@contoh.test',
            'password' => 'password',
            'device_name' => 'Redmi Note 13',
        ]);

        expect($user->personalSpace())->type->toBe(SpaceType::Personal);
        $this->assertDatabaseHas('task_lists', ['space_id' => $user->personalSpace()->id, 'name' => 'Tugas']);
    });
});

describe('destroy', function () {
    it('revokes the token in use', function () {
        $user = User::factory()->create();
        $token = $user->createToken('Redmi Note 13')->plainTextToken;
        $user->createToken('Tablet');

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'Redmi Note 13']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Tablet']);
    });

    it('removes the given FCM token and keeps the other devices of the user', function () {
        $user = User::factory()->create();
        $token = $user->createToken('Redmi Note 13')->plainTextToken;
        DeviceToken::factory()->for($user)->create(['token' => 'fcm-hp-ini']);
        DeviceToken::factory()->for($user)->create(['token' => 'fcm-hp-lain']);

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout', ['fcm_token' => 'fcm-hp-ini']);

        $response->assertNoContent();
        $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm-hp-ini']);
        $this->assertDatabaseHas('device_tokens', ['token' => 'fcm-hp-lain']);
    });

    it('does not remove an FCM token that belongs to another account', function () {
        $user = User::factory()->create();
        $token = $user->createToken('Redmi Note 13')->plainTextToken;
        DeviceToken::factory()->create(['token' => 'fcm-orang-lain']);

        $this->withToken($token)->postJson('/api/v1/auth/logout', ['fcm_token' => 'fcm-orang-lain']);

        $this->assertDatabaseHas('device_tokens', ['token' => 'fcm-orang-lain']);
    });

    it('returns 401 when no token is sent', function () {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    });
});

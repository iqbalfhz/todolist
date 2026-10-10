<?php

use App\Enums\SpaceType;
use App\Models\User;

/**
 * A registration payload that passes validation.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return [
        'name' => 'Iqbal',
        'email' => 'iqbal@contoh.test',
        'password' => 'rahasia-sekali',
        'device_name' => 'Redmi Note 13',
        'timezone' => 'Asia/Makassar',
        ...$overrides,
    ];
}

it('registers an account and returns a token with the user', function () {
    $response = $this->postJson('/api/v1/auth/register', registrationPayload());

    $response->assertCreated()
        ->assertJsonPath('data.user.name', 'Iqbal')
        ->assertJsonPath('data.user.email', 'iqbal@contoh.test')
        ->assertJsonPath('data.user.timezone', 'Asia/Makassar')
        ->assertJsonPath('data.user.color', 'mawar')
        ->assertJsonPath('data.user.default_currency', 'IDR');
    $this->assertDatabaseHas('users', ['email' => 'iqbal@contoh.test', 'timezone' => 'Asia/Makassar']);
    $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Redmi Note 13']);
});

it('issues a token that authenticates the new account', function () {
    $token = $this->postJson('/api/v1/auth/register', registrationPayload())->json('data.token');

    $response = $this->withToken($token)->getJson('/api/v1/me');

    $response->assertOk()->assertJsonPath('data.email', 'iqbal@contoh.test');
});

it('creates a personal space with a list named Tugas', function () {
    $this->postJson('/api/v1/auth/register', registrationPayload());

    $user = User::query()->where('email', 'iqbal@contoh.test')->sole();
    $space = $user->personalSpace();
    expect($space)
        ->type->toBe(SpaceType::Personal)
        ->timezone->toBe('Asia/Makassar')
        ->owner_id->toBe($user->id);
    $this->assertDatabaseHas('space_members', ['space_id' => $space->id, 'user_id' => $user->id, 'role' => 'owner']);
    $this->assertDatabaseHas('task_lists', ['space_id' => $space->id, 'name' => 'Tugas', 'created_by' => $user->id]);
});

it('uses Asia/Jakarta when no timezone is sent', function () {
    $response = $this->postJson('/api/v1/auth/register', registrationPayload(['timezone' => null]));

    $response->assertCreated()->assertJsonPath('data.user.timezone', 'Asia/Jakarta');
});

it('stores the email in lowercase', function () {
    $response = $this->postJson('/api/v1/auth/register', registrationPayload(['email' => 'Iqbal@Contoh.Test']));

    $response->assertCreated()->assertJsonPath('data.user.email', 'iqbal@contoh.test');
});

it('returns 422 on the email field when the email is already registered', function () {
    User::factory()->create(['email' => 'iqbal@contoh.test']);

    $response = $this->postJson('/api/v1/auth/register', registrationPayload());

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.email.0', 'Email sudah ada sebelumnya.');
    $this->assertDatabaseCount('users', 1);
});

it('returns 422 when the password is shorter than 8 characters', function () {
    $response = $this->postJson('/api/v1/auth/register', registrationPayload(['password' => 'pendek']));

    $response->assertUnprocessable()->assertJsonPath('errors.password.0', 'Kata sandi minimal berisi 8 karakter.');
    $this->assertDatabaseEmpty('users');
});

it('returns 422 naming every required field that is missing', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password', 'device_name']);
});

it('returns 422 for invalid values', function (array $overrides, string $field) {
    $response = $this->postJson('/api/v1/auth/register', registrationPayload($overrides));

    $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
    $this->assertDatabaseEmpty('users');
})->with([
    'a name longer than 50 characters' => [['name' => str_repeat('a', 51)], 'name'],
    'an email without a domain' => [['email' => 'bukan-email'], 'email'],
    'a timezone that is not an IANA name' => [['timezone' => 'WIB'], 'timezone'],
]);

it('returns 429 after five registrations from the same IP within a minute', function () {
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/register', []);
    }

    $response = $this->postJson('/api/v1/auth/register', registrationPayload());

    $response->assertTooManyRequests()
        ->assertJsonPath('code', 'too_many_requests')
        ->assertHeader('Retry-After');
    $this->assertDatabaseEmpty('users');
});

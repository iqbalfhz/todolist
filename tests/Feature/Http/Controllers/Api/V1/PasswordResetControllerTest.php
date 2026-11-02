<?php

use App\Models\DeviceToken;
use App\Models\User;
use App\Models\VerificationCode;
use Database\Factories\VerificationCodeFactory;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * A request to reset the password that passes validation.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function passwordResetPayload(array $overrides = []): array
{
    return [
        'email' => 'nama@email.com',
        'code' => VerificationCodeFactory::CODE,
        'password' => 'kata-sandi-baru',
        'device_name' => 'Redmi Note 14 Pro+ 5G',
        ...$overrides,
    ];
}

it('sets the new password, signs out every device, verifies the email, and signs in this one', function () {
    $user = User::factory()->unverified()->withPersonalSpace()->create(['email' => 'nama@email.com', 'password' => 'kata-sandi-lama']);
    $oldToken = $user->createToken('HP lama')->plainTextToken;
    DeviceToken::factory()->for($user)->create();
    VerificationCode::factory()->for($user)->create();

    $response = $this->postJson('/api/v1/auth/reset-password', passwordResetPayload(['email' => 'NAMA@email.com']));

    $response->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', 'nama@email.com');
    $user->refresh();
    expect(Hash::check('kata-sandi-baru', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($response->json('data.user.email_verified_at'))->not->toBeNull()
        ->and(PersonalAccessToken::findToken($oldToken))->toBeNull()
        ->and(PersonalAccessToken::findToken($response->json('data.token'))?->name)->toBe('Redmi Note 14 Pro+ 5G')
        ->and($user->tokens()->count())->toBe(1)
        ->and($user->deviceTokens()->count())->toBe(0);
    $this->assertDatabaseCount('verification_codes', 0);
});

it('signs in with the new password afterwards, and no longer with the old one', function () {
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com', 'password' => 'kata-sandi-lama']);
    VerificationCode::factory()->for($user)->create();
    $this->postJson('/api/v1/auth/reset-password', passwordResetPayload())->assertOk();

    $this->postJson('/api/v1/auth/login', ['email' => 'nama@email.com', 'password' => 'kata-sandi-baru', 'device_name' => 'Laptop'])->assertOk();
    $this->postJson('/api/v1/auth/login', ['email' => 'nama@email.com', 'password' => 'kata-sandi-lama', 'device_name' => 'Laptop'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'invalid_credentials');
});

it('keeps the time the email was verified when it was verified before', function () {
    $this->travelTo('2026-10-11 08:00:00');
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com']);
    $this->travelTo('2026-11-02 10:00:00');
    VerificationCode::factory()->for($user)->create();

    $this->postJson('/api/v1/auth/reset-password', passwordResetPayload())->assertOk();

    expect($user->fresh()->email_verified_at->format('Y-m-d H:i:s'))->toBe('2026-10-11 08:00:00');
});

it('lets an account from Google without a password set one', function () {
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com', 'password' => null, 'google_id' => '110248495921238986420']);
    VerificationCode::factory()->for($user)->create();

    $this->postJson('/api/v1/auth/reset-password', passwordResetPayload())->assertOk();

    expect(Hash::check('kata-sandi-baru', $user->fresh()->password))->toBeTrue();
});

it('creates the personal space of an account made outside registration, as login does', function () {
    $user = User::factory()->create(['email' => 'nama@email.com']);
    VerificationCode::factory()->for($user)->create();

    $this->postJson('/api/v1/auth/reset-password', passwordResetPayload())->assertOk();

    expect($user->personalSpace())->not->toBeNull();
});

it('returns 422 for a wrong code, counts the try, and changes nothing', function () {
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com', 'password' => 'kata-sandi-lama']);
    $token = $user->createToken('HP lama')->plainTextToken;
    $code = VerificationCode::factory()->for($user)->create();

    $response = $this->postJson('/api/v1/auth/reset-password', passwordResetPayload(['code' => '000000']));

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('errors.code.0', 'Kode salah atau sudah tidak berlaku. Minta kode baru kalau perlu.')
        ->assertJsonMissingPath('data');
    expect($code->fresh()->attempts)->toBe(1)
        ->and(Hash::check('kata-sandi-lama', $user->fresh()->password))->toBeTrue()
        ->and(PersonalAccessToken::findToken($token))->not->toBeNull();
});

it('ends the code after five wrong tries, so even the right code fails then', function () {
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com']);
    $code = VerificationCode::factory()->for($user)->create();

    foreach (range(1, 4) as $try) {
        $this->postJson('/api/v1/auth/reset-password', passwordResetPayload(['code' => '000000']))->assertUnprocessable();
    }

    expect($code->fresh()->attempts)->toBe(4);

    $this->postJson('/api/v1/auth/reset-password', passwordResetPayload(['code' => '000000']))->assertUnprocessable();

    $this->assertModelMissing($code);
    $this->postJson('/api/v1/auth/reset-password', passwordResetPayload())->assertUnprocessable();
});

it('returns 422 for a code that expired, and removes it', function () {
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com']);
    $code = VerificationCode::factory()->for($user)->expired()->create();

    $response = $this->postJson('/api/v1/auth/reset-password', passwordResetPayload());

    $response->assertUnprocessable()->assertJsonPath('errors.code.0', 'Kode salah atau sudah tidak berlaku. Minta kode baru kalau perlu.');
    $this->assertModelMissing($code);
});

it('still accepts the code in its last minute', function () {
    $this->travelTo('2026-11-02 10:00:00');
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com']);
    VerificationCode::factory()->for($user)->create();
    $this->travelTo('2026-11-02 10:59:59');

    $this->postJson('/api/v1/auth/reset-password', passwordResetPayload())->assertOk();
});

it('answers an email without an account the same way as a wrong code', function () {
    $response = $this->postJson('/api/v1/auth/reset-password', passwordResetPayload(['email' => 'tidak-ada@email.com']));

    $response->assertUnprocessable()
        ->assertJsonPath('errors.code.0', 'Kode salah atau sudah tidak berlaku. Minta kode baru kalau perlu.');
});

it('does not accept a code for verifying the email', function () {
    $user = User::factory()->unverified()->withPersonalSpace()->create(['email' => 'nama@email.com']);
    $verification = VerificationCode::factory()->for($user)->forEmailVerification()->create();

    $response = $this->postJson('/api/v1/auth/reset-password', passwordResetPayload());

    $response->assertUnprocessable();
    expect($verification->fresh()->attempts)->toBe(0);
});

it('does not accept the code of another account', function () {
    User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com']);
    VerificationCode::factory()->create();

    $response = $this->postJson('/api/v1/auth/reset-password', passwordResetPayload());

    $response->assertUnprocessable();
});

it('returns 422 for fields that are missing or not valid, without using up a try', function (array $overrides, string $field) {
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com']);
    $code = VerificationCode::factory()->for($user)->create();

    $response = $this->postJson('/api/v1/auth/reset-password', passwordResetPayload($overrides));

    $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
    expect($code->fresh()->attempts)->toBe(0);
})->with([
    'no email' => [['email' => null], 'email'],
    'a code of 5 digits' => [['code' => '48291'], 'code'],
    'a code of 7 digits' => [['code' => '4829131'], 'code'],
    'a code with letters' => [['code' => '48291a'], 'code'],
    'a code sent as a number' => [['code' => 482913], 'code'],
    'a short password' => [['password' => 'pendek'], 'password'],
    'no device name' => [['device_name' => null], 'device_name'],
]);

it('allows 10 tries per minute from one IP address', function () {
    foreach (range(1, 10) as $try) {
        $this->postJson('/api/v1/auth/reset-password', passwordResetPayload(['code' => '000000']))->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/reset-password', passwordResetPayload())->assertTooManyRequests();
});

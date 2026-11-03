<?php

use App\Enums\AccountNotice;
use App\Mail\AccountNoticeMail;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('changes the password, signs the other phones out, keeps this phone and its push token, and emails a notice', function () {
    Mail::fake();
    $user = User::factory()->withPersonalSpace()->create(['email' => 'nama@email.com', 'name' => 'Iqbal']);
    $thisPhone = $user->createToken('HP ini');
    $otherPhone = $user->createToken('HP lain');
    $thisPush = DeviceToken::factory()->for($user)->create(['token' => 'token-fcm-hp-ini']);
    $otherPush = DeviceToken::factory()->for($user)->create(['token' => 'token-fcm-hp-lain']);

    $response = $this->withToken($thisPhone->plainTextToken)->putJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'kata-sandi-baru',
        'fcm_token' => 'token-fcm-hp-ini',
    ]);

    $response->assertNoContent();
    expect(Hash::check('kata-sandi-baru', $user->fresh()->password))->toBeTrue();
    $this->assertModelExists($thisPhone->accessToken);
    $this->assertModelMissing($otherPhone->accessToken);
    $this->assertModelExists($thisPush);
    $this->assertModelMissing($otherPush);
    Mail::assertQueuedCount(1);
    Mail::assertQueued(AccountNoticeMail::class, fn (AccountNoticeMail $mail): bool => $mail->hasTo('nama@email.com')
        && $mail->notice === AccountNotice::PasswordChanged
        && $mail->recipientName === 'Iqbal');
});

it('removes every push token of the account when this phone sends none', function () {
    Mail::fake();
    $user = User::factory()->withPersonalSpace()->create();
    $thisPhone = $user->createToken('HP ini');
    DeviceToken::factory()->for($user)->count(2)->create();

    $this->withToken($thisPhone->plainTextToken)
        ->putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'kata-sandi-baru'])
        ->assertNoContent();

    $this->assertDatabaseMissing('device_tokens', ['user_id' => $user->id]);
    $this->assertModelExists($thisPhone->accessToken);
});

it('lets an account from Google set a password with an ID token of its Google account', function () {
    Mail::fake();
    config(['services.google.client_id' => GOOGLE_TEST_CLIENT_ID]);
    fakeGoogleKeys();
    $user = User::factory()->withPersonalSpace()->create(['google_id' => '110248495921238986420', 'password' => null]);
    $this->actingAs($user, 'sanctum');

    $response = $this->putJson('/api/v1/me/password', ['id_token' => googleIdToken(), 'password' => 'kata-sandi-baru']);

    $response->assertNoContent();
    expect(Hash::check('kata-sandi-baru', $user->fresh()->password))->toBeTrue();
    $this->getJson('/api/v1/me')->assertJsonPath('data.has_password', true);
});

it('returns 422 with the invalid_credentials code for a wrong current password and changes nothing', function (array $attributes) {
    Mail::fake();
    $user = User::factory()->withPersonalSpace()->create($attributes);
    $otherPhone = $user->createToken('HP lain');
    $this->actingAs($user, 'sanctum');

    $response = $this->putJson('/api/v1/me/password', ['current_password' => 'bukan-kata-sandinya', 'password' => 'kata-sandi-baru']);

    $response->assertUnprocessable()->assertExactJson([
        'message' => 'Kata sandi saat ini salah.',
        'code' => 'invalid_credentials',
    ]);
    expect(Hash::check('kata-sandi-baru', (string) $user->fresh()->password))->toBeFalse();
    $this->assertModelExists($otherPhone->accessToken);
    Mail::assertNothingQueued();
})->with([
    'an account with a password' => [[]],
    'an account from Google without a password' => [['google_id' => '110248495921238986420', 'password' => null]],
]);

it('returns 422 with the google_token_invalid code for a token of a Google account that is not linked', function () {
    Mail::fake();
    config(['services.google.client_id' => GOOGLE_TEST_CLIENT_ID]);
    fakeGoogleKeys();
    $user = User::factory()->withPersonalSpace()->create(['google_id' => '209876543210987654321']);
    $this->actingAs($user, 'sanctum');

    $response = $this->putJson('/api/v1/me/password', ['id_token' => googleIdToken(), 'password' => 'kata-sandi-baru']);

    $response->assertUnprocessable()->assertExactJson([
        'message' => 'Pilih akun Google yang tersambung ke akun ini.',
        'code' => 'google_token_invalid',
    ]);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    Mail::assertNothingQueued();
});

it('returns 422 with the validation_failed code for invalid values', function (array $body, string $field, string $message) {
    actingAsApiUser();

    $response = $this->putJson('/api/v1/me/password', $body);

    $response->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath("errors.{$field}.0", $message);
})->with([
    'a new password shorter than 8 characters' => [['current_password' => 'password', 'password' => 'pendek'], 'password', 'Kata sandi minimal berisi 8 karakter.'],
    'no new password' => [['current_password' => 'password'], 'password', 'Kata sandi wajib diisi.'],
    'neither the current password nor a Google token' => [['password' => 'kata-sandi-baru'], 'current_password', 'Kata sandi saat ini wajib diisi bila tidak terdapat token Google.'],
]);

it('allows 5 tries per 15 minutes for one account', function () {
    $this->travelTo('2026-11-03 10:00:00');
    actingAsApiUser();

    foreach (range(1, 5) as $try) {
        $this->putJson('/api/v1/me/password', ['current_password' => 'tebakan-salah', 'password' => 'kata-sandi-baru'])->assertUnprocessable();
    }

    $this->putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'kata-sandi-baru'])->assertTooManyRequests();

    $this->travelTo('2026-11-03 10:14:59');
    $this->putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'kata-sandi-baru'])->assertTooManyRequests();

    $this->travelTo('2026-11-03 10:15:01');
    Mail::fake();
    $this->putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'kata-sandi-baru'])->assertNoContent();
});

it('returns 401 when no token is sent', function () {
    $this->putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'kata-sandi-baru'])->assertUnauthorized();
});
